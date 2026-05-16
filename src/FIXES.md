# FIXES.md — KoreSearch Bug Report & Upgrade Log

**Project:** KoreSearch LMS & Job Platform
**Original Laravel Version:** 10.x
**Upgraded To:** Laravel 12.x
**PHP Requirement:** 8.2+

---

## 1. How Did You Find the Bugs?

**Process:**

1. **Code audit first** — read every controller, model, view, route, and middleware file before touching anything. Mapped all data flows end-to-end.
2. **Cross-referenced routes with controllers** — found that `route('home.index')` was called in `AuthController` but never registered in `routes/web.php`.
3. **Traced the JS** — saw `js/application.js` referenced in the layout, then checked `public/js/` — only `app.js` exists on disk.
4. **Inspected AJAX fetch** — JS reads `meta[name="csrf-token"]` but the HTML meta tag had `name="csrf"` — mismatch causes all AJAX requests to fail with 403.
5. **Followed the cart session** — found type mismatch (string vs int) causing `remove` to never work. Found `count - 1` bug returning wrong cart badge count.
6. **Checked login redirect** — `AuthController::login` always redirected to `route('dashboard')` which is behind `role:admin` middleware — students hit 403 immediately after login.
7. **Checked navbar** — "Dashboard" link shown to all authenticated users regardless of role, sending students to an admin-only route.
8. **Checked course upload form** — `level` field was missing from both the HTML form and backend validation, yet the `courses` table has a `level` column.
9. **Setup failure investigation** — `artisan` not found because original project zip was missing Laravel core scaffold files. Resolved by merging fresh Laravel scaffold.

---

## 2. How Did You Fix Each Bug?

---

### SETUP ISSUE — Missing Core Laravel Files

**Problem:** The initial project archive was missing essential Laravel framework files required to boot the application:

- `artisan`
- `bootstrap/` directory and all contents
- `config/` directory and all configuration files
- `storage/` directory tree
- `tests/` directory
- All default middleware classes (`Authenticate.php`, `RedirectIfAuthenticated.php`, `EncryptCookies.php`, `PreventRequestsDuringMaintenance.php`, `TrimStrings.php`, `TrustHosts.php`, `TrustProxies.php`, `ValidateSignature.php`, `VerifyCsrfToken.php`)
- `phpunit.xml`, `package.json`, `vite.config.js`

**Impact:** Setup failed at `composer install` post-dump-autoload hook — the hook runs `php artisan package:discover` which requires the `artisan` file to exist.

**Fix:** Downloaded a fresh `laravel/laravel:^10.0` scaffold via `composer create-project`, then merged it with the existing custom source files. Custom application files (controllers, models, views, routes, migrations) took priority over framework defaults.

---

### BUG 1 — Wrong Route Name on Register Redirect

**File:** `app/Http/Controllers/AuthController.php` → `register()` method
**Problem:** `return redirect()->route('home.index')` — the route `home.index` does not exist anywhere in `routes/web.php`. This caused a `RouteNotFoundException` crash on every successful registration.
**Fix:** Changed to `return redirect()->route('home')`.

---

### BUG 2 — Login Redirects All Users to Admin Dashboard (403)

**File:** `app/Http/Controllers/AuthController.php` → `login()` method
**Problem:** After successful login, all users were redirected to `route('dashboard')` regardless of their role. The `/dashboard` route is protected by `role:admin` middleware, so students and instructors immediately received a 403 Unauthorized error after logging in.
**Fix:** Added role-based redirect logic after login:

```php
$user = Auth::user();
if ($user->isAdmin()) {
    return redirect()->intended(route('dashboard'));
}
return redirect()->intended(route('home'));
```

---

### BUG 3 — Navbar Shows "Dashboard" Link for All Logged-in Users (403 for Students)

**File:** `resources/views/layouts/app.blade.php`
**Problem:** The navbar showed a "Dashboard" link to ALL authenticated users regardless of role. Students clicking it hit the `role:admin` middleware and received 403 Unauthorized — effectively trapping them after login with no accessible page.
**Fix:** Added role-based nav link:

```blade
@auth
    @if(Auth::user()->isAdmin())
        <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
    @else
        <li><a href="{{ route('student.dashboard') }}">My Learning</a></li>
    @endif
@endauth
```

---

### BUG 4 — Cart Badge Shows Wrong Count After Add to Cart

**File:** `app/Http/Controllers/CartController.php` → `add()` method
**Problem:** The JSON response returned `count($cart) - 1` stored as `$countBeforeUpdate`. This meant clicking "Add to Cart" updated the badge to the count _before_ the item was added, permanently showing a stale number.
**Fix:** Return `count($cart)` — the actual count after the item has been added to the array.

---

### BUG 5 — Remove From Cart Never Works (Type Mismatch)

**File:** `app/Http/Controllers/CartController.php` → `remove()` method
**Problem:** Session stored course IDs as strings (`$cart[] = (string) $course->id`) but the filter used strict comparison `$id !== $course->id` where `$course->id` is an integer. `"5" !== 5` is `true` in PHP strict comparison, so the filter never removed anything.
**Fix:** Cast both sides to `int`:

```php
$cart = array_filter($cart, fn($id) => (int)$id !== (int)$course->id);
```

---

### BUG 6 — AJAX Add to Cart Always Returns 403 (Wrong CSRF Meta Name)

**File:** `resources/views/layouts/app.blade.php`
**Problem:** The meta tag was `<meta name="csrf" content="...">`. The JavaScript reads `document.querySelector('meta[name="csrf-token"]')`. Because the name didn't match, the token was `null`, and every AJAX POST was rejected with 403 Forbidden.
**Fix:** Changed to:

```html
<meta name="csrf-token" content="{{ csrf_token() }}" />
```

---

### BUG 7 — All JavaScript Dead (Wrong Script Filename)

**File:** `resources/views/layouts/app.blade.php`
**Problem:** Layout loaded `<script src="{{ asset('js/application.js') }}">`. The actual file on disk is `public/js/app.js`. This caused a 404 for the script, making ALL JavaScript non-functional — hamburger menu, user dropdown, Add to Cart AJAX, checkout form validation, dashboard tabs — everything broken silently.
**Fix:** Changed to:

```html
<script src="{{ asset('js/app.js') }}"></script>
```

---

### BUG 8 — Level Field Missing from Course Upload (Form + Validation + Controller)

**File:** `resources/views/dashboard/index.blade.php` + `app/Http/Controllers/DashboardController.php`
**Problem:** The course upload form had no `level` dropdown input. The controller validation also did not include `level`. So every course uploaded via the admin dashboard silently defaulted to whatever the database column default was (`beginner`), regardless of what the instructor intended.
**Fix:**

- Added `<select name="level">` with beginner/intermediate/advanced options to the upload form.
- Added `'level' => ['required', 'in:beginner,intermediate,advanced']` to `storeCourse()` validation.
- Added `'level' => $request->level` to `Course::create()`.

---

### BUG 9 — No Student Dashboard (Students Had No Accessible Page)

**Problem:** No student-facing dashboard existed. After login, students were redirected to `/dashboard` (admin-only, 403). There was no page showing enrolled courses or student profile. Students were essentially locked out of the application.
**Fix:**

- Created `app/Http/Controllers/StudentController.php`
- Created `resources/views/student/dashboard.blade.php` showing user profile and enrolled courses with order history
- Added route `GET /my-learning` named `student.dashboard` inside `auth` middleware group in `routes/web.php`

---

## 3. Laravel 10 → 12 Upgrade

### Files Deleted

| File                                         | Reason                                                  |
| -------------------------------------------- | ------------------------------------------------------- |
| `app/Http/Kernel.php`                        | Not used in Laravel 12; replaced by `bootstrap/app.php` |
| `app/Providers/AuthServiceProvider.php`      | Consolidated into `AppServiceProvider`                  |
| `app/Providers/BroadcastServiceProvider.php` | Consolidated into `AppServiceProvider`                  |
| `app/Providers/EventServiceProvider.php`     | Consolidated into `AppServiceProvider`                  |
| `app/Providers/RouteServiceProvider.php`     | Routing now configured in `bootstrap/app.php`           |

### Files Added

| File                      | Reason                                                                    |
| ------------------------- | ------------------------------------------------------------------------- |
| `bootstrap/providers.php` | New Laravel 12 requirement — replaces providers array in `config/app.php` |

### Files Modified

| File                                   | Change                                                                                                                                                                                                                                                       |
| -------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `composer.json`                        | `laravel/framework: ^12.0`, `php: ^8.2`, `laravel/sanctum: ^4.0`, `phpunit/phpunit: ^11.0`, `nunomaduro/collision: ^8.0`, removed `laravel/sail`                                                                                                             |
| `bootstrap/app.php`                    | Full rewrite — now uses `Application::configure()` fluent API; middleware aliases registered here                                                                                                                                                            |
| `config/app.php`                       | Removed `use Illuminate\Support\Facades\Facade`, removed `use Illuminate\Support\ServiceProvider`, replaced `ServiceProvider::defaultProviders()->merge([...])->toArray()` with `[]`, replaced `Facade::defaultAliases()->merge([...])->toArray()` with `[]` |
| `app/Http/Controllers/Controller.php`  | Removed L10 traits `AuthorizesRequests` and `ValidatesRequests`; simplified to plain abstract class (L12 style)                                                                                                                                              |
| `app/Providers/AppServiceProvider.php` | Merged event listeners and rate limiter from deleted providers; added `Paginator::useBootstrapFive()`                                                                                                                                                        |

---

## 4. What Challenges Did You Face?

**Missing scaffold files:**
The biggest blocker was the missing Laravel scaffold. The project zip included only custom app files — none of the framework core. Composer appeared to work until it hit the `php artisan package:discover` post-install hook and crashed. The fix required understanding that the project needed bootstrapping from a fresh install before merging custom code.

**`config/app.php` L10 → L12:**
The `providers` and `aliases` keys in `config/app.php` used fluent builder calls (`ServiceProvider::defaultProviders()->merge([...])->toArray()` and `Facade::defaultAliases()->merge([...])->toArray()`) that are incompatible with how Laravel 12 bootstraps configuration. This caused the cryptic `array_merge(): Argument #2 must be of type array, int given` error during setup. The fix was replacing both with plain empty arrays `[]` since Laravel 12 discovers providers via `bootstrap/providers.php`.

**Stale vendor after upgrade:**
After updating `composer.json`, the vendor directory still contained L10 packages. `composer update` reported "Nothing to install" because the lock file hadn't been regenerated cleanly. Required deleting `vendor/` and `composer.lock` entirely and running a fresh `composer install`.

---

## 5. Suggestions & Comments

**Auth redirect should always be role-aware.** A single redirect-to-dashboard for all users is an easy mistake when admin features are built first, but it completely locks out non-admin users.

**Project zips for handoff should be verified with `php artisan --version`.** If artisan can't run after `composer install`, the zip is incomplete. A simple CI check or README note would catch this instantly.

**Slug uniqueness:** `DashboardController::storeCourse` should append a random string to the slug to prevent duplicate slug errors when uploading similarly-titled courses. Consider `Str::slug($title) . '-' . Str::random(4)`.

**Future improvements worth considering:**

- Increment `enrolled_count` on Course when an Order is created
- Course progress tracking per student (lesson completion)
- Instructor-specific dashboard separate from admin
- Email confirmation after successful checkout
- bKash order status verification flow (manual admin approval or webhook)
