# Testing Documentation

## 📊 **Overall Test Status**

```
Tests:    194 passed (493 assertions)
Duration: 21.95s
Status:   ✅ ALL TESTS PASSING
```

## System Settings Tests

### Overview
System Settings tests have been updated to reflect the current implementation which only includes **Authentication Settings**.

### Settings Tested

#### Authentication Group
- `enable_manual_registration` - Toggle manual registration (without SSO)
- `enable_sso_login` - Toggle SSO login feature

### Test Files

#### Feature Tests: `tests/Feature/SystemSettingTest.php`
Tests full HTTP request/response flow with authorization.

**Tests:**
1. ✅ `test_admin_with_permission_can_view_settings_page`
   - Admin dengan permission `system.settings` dapat akses halaman settings
   
2. ✅ `test_user_without_permission_cannot_view_settings_page`
   - User tanpa permission tidak dapat akses halaman settings (403 Forbidden)
   
3. ✅ `test_admin_with_permission_can_update_settings`
   - Admin dapat mengupdate authentication settings
   - Test update dari '1' (aktif) ke '0' (nonaktif)
   
4. ✅ `test_update_settings_handles_exceptions_and_shows_error`
   - Memastikan error handling berfungsi dengan baik

#### Unit Tests: `tests/Unit/Services/SystemSettingServiceTest.php`
Tests service layer logic without HTTP layer.

**Tests:**
1. ✅ `it_returns_settings_grouped_for_page`
   - Service mengembalikan data settings yang sudah di-group
   - Memastikan group 'authentication' ada dalam hasil
   
2. ✅ `it_updates_multiple_settings_and_ignores_unknown_keys`
   - Service dapat mengupdate multiple settings sekaligus
   - Unknown keys diabaikan tanpa error

### Running Tests

```bash
# Run all system settings tests
php artisan test --filter SystemSetting

# Run specific test class
php artisan test tests/Feature/SystemSettingTest.php
php artisan test tests/Unit/Services/SystemSettingServiceTest.php

# Run with coverage
php artisan test --filter SystemSetting --coverage
```

### Test Results

```
Tests:  6 passed (17 assertions)
Duration: 1.69s

✓ it returns settings grouped for page
✓ it updates multiple settings and ignores unknown keys
✓ admin with permission can view settings page
✓ user without permission cannot view settings page
✓ admin with permission can update settings
✓ update settings handles exceptions and shows error
```

### Test Data Structure

**Create Settings:**
```php
SystemSetting::create([
    'group' => 'authentication',
    'key' => 'enable_manual_registration',
    'value' => '1',
    'type' => 'boolean',
    'is_public' => true,
]);
```

**Update Settings:**
```php
$payload = [
    'settings' => [
        'enable_manual_registration' => '0',
        'enable_sso_login' => '0',
    ],
];
```

### Notes

- Tests menggunakan `DatabaseMigrations` trait untuk clean database state
- Permission cache di-forget setelah setiap test untuk avoid cache issues
- Semua tests isolated dan tidak depend satu sama lain
- Boolean values disimpan sebagai string '0' atau '1' di database

### Removed Settings (Not Tested Anymore)

The following settings groups were removed as they are not currently used:
- ❌ General Settings (app_name, app_short_name, institution_name, timezone)
- ❌ Contact Settings (contact_email, contact_phone, contact_address)
- ❌ Peminjaman Settings (max_booking_days, min_booking_advance_days, auto_approve_mahasiswa, require_surat_peminjaman)

These can be added back when the related features are implemented.
