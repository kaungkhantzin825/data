# Complete Codebase Analysis

## 📋 Project Overview

**Project Name:** Pipeline - Lead Management System  
**Tech Stack:** Laravel 10 + Vue 3 + Inertia.js + Vuetify 4  
**Architecture:** Multi-tenant SaaS with separate databases per tenant  
**Authentication:** Laravel Sanctum (API) + Session-based (Web)  
**Permissions:** Spatie Laravel Permission package

---

## 🏗️ Architecture

### Multi-Tenant Database Structure

**Three Database Connections:**

1. **`mysql_central`** (Central/Admin Database)
   - Tables: `users`, `plans`, `roles`, `permissions`, `tenants`, `activity_logs`, `database_backups`
   - Never switched by middleware
   - Stores all user accounts, authentication, and tenant metadata

2. **`mysql`** (Default Connection)
   - Same as `mysql_central` in configuration
   - Used for general queries

3. **`tenant`** (Dynamic Tenant Database)
   - Tables: `leads`, `tenant_field_options`
   - Switched per-request by `InitializeTenancyByAuthUser` middleware
   - Each tenant has their own isolated database (e.g., `tenant_abc123_db`)

### Tenant Isolation Flow

```
1. User logs in → Session created
2. Request hits middleware → Checks auth()->user()->tenant_id
3. Middleware finds Tenant record → Gets tenancy_db_name
4. Middleware reconfigures 'tenant' connection → Points to tenant's database
5. Lead and TenantFieldOption models use 'tenant' connection
6. User model always uses 'mysql_central' connection
```

---

## 👥 User Roles & Permissions

### Role Hierarchy

1. **Super Admin** (`is_admin = true`)
   - Full system access
   - Can manage all tenants
   - No tenant database switching
   - Can create/edit/delete users, roles, permissions, plans

2. **Tenant Owner** (User role with `tenant_id = null` or `tenant_id = user.id`)
   - Owns a tenant account
   - Can see all leads in their tenant
   - Can manage staff users under their tenant
   - Limited by SaaS plan (staff_limit, plan_expired_at)

3. **Staff** (Staff role with `tenant_id = owner_id`)
   - Works under a tenant owner
   - Can only see leads they created (`created_by = user.id`)
   - Cannot manage other users
   - Requires approval (`is_active = true`) to login

### Permission System

**Granular Permissions (Spatie):**
- `view_dashboard` - Access dashboard
- `menu_lists` - View leads list
- `menu_create` - Create new leads
- `menu_user_management` - Access user management
- `submenu_users`, `submenu_roles`, `submenu_permissions` - User management tabs
- `create_users`, `edit_users`, `delete_users` - User CRUD
- `manage_roles` - Role and permission management
- `menu_setting` - Access settings page
- `setting_profile`, `setting_backup`, `setting_activity`, `setting_user_status` - Settings tabs
- `menu_plans` - Access plan management
- `setting_tenant_fields` - Manage dropdown options
- `action_upload_lead`, `action_download_csv` - Lead import/export
- `section_*` - Form section visibility (section_business_info, section_contact_info, etc.)

---

## 📊 Database Schema

### Central Database Tables

#### `users`
```sql
- id (PK)
- tenant_id (nullable, string) - UUID of tenant or owner user ID
- plan_id (FK to plans)
- name, company_name, phone, email
- password
- is_admin (boolean) - Super admin flag
- is_active (boolean) - Login approval status
- profile_logo (string, nullable)
- plan_expired_at (date, nullable)
- role (string, default 'user') - Legacy field
- created_at, updated_at, deleted_at
```

#### `tenants`
```sql
- id (PK, string/UUID)
- user_id (integer) - Owner user ID
- tenancy_db_name (string) - Database name (e.g., tenant_abc123_db)
- created_at, updated_at
```

#### `plans`
```sql
- id (PK)
- name (string)
- staff_limit (integer) - Max staff users
- duration_in_days (integer) - Plan duration
- description (text, nullable)
- created_at, updated_at
```

#### `roles` & `permissions` (Spatie)
```sql
- Standard Spatie permission tables
- role_has_permissions, model_has_roles, model_has_permissions
```

#### `activity_logs`
```sql
- id (PK)
- user_id (FK to users)
- action (string) - e.g., 'user_created', 'user_updated'
- description (text)
- ip_address, user_agent
- created_at, updated_at
```

### Tenant Database Tables

#### `leads` (in each tenant database)
```sql
- id (PK)
- uuid (unique, auto-generated)
- tenant_id (integer, nullable)
- user_id (integer, nullable) - Legacy field
- created_by (integer) - User ID who created the lead

# Business Information
- business_name (required)
- contact_name (required)
- first_name, last_name
- biz_type (Business Type)

# Contact Information
- contact_email
- phone (primary contact)
- secondary_contact_number
- division, township, address

# Lead Details
- source (Lead Source)
- channel (Sales Channel)
- product, package, plan
- amount (decimal) - Final amount
- package_total (decimal) - Package price
- discount (decimal)
- status (New, followup, Contracted, Cancelled)

# Dates
- installation_appointment (datetime)
- est_contract_date, est_start_date, est_follow_up_date
- contracted_date (NEW) - When lead was contracted
- installation_appointment_date (NEW) - Scheduled installation

# Additional Fields
- weighted, potential
- is_referral (boolean)
- meeting_note, next_step, note
- customer_note (NEW) - Contract notes

- created_at, updated_at, deleted_at
```

#### `tenant_field_options` (in each tenant database)
```sql
- id (PK)
- tenant_id (integer) - Owner user ID (not UUID)
- field_name (string) - biz_type, source, division, township, product, channel, package
- option_value (string) - The dropdown option text
- created_at, updated_at
```

---

## 🎨 Frontend Structure

### Vue Pages (Inertia.js)

1. **Dashboard.vue** - Main dashboard with tabs
   - Tab: `dashboard` - Summary reports, metrics, charts
   - Tab: `lists` - Lead list with filters and pagination
   - Tab: `create` - Multi-section lead creation form
   - Tab: `upload` - CSV import interface

2. **UserManagement.vue** - User, role, and permission management
   - Tab: `users` - User list with CRUD operations
   - Tab: `roles` - Role management with permission assignment
   - Tab: `permissions` - Permission list

3. **Settings.vue** - System settings
   - Tab: `profile` - User profile, password, avatar upload
   - Tab: `backup` - Database backup generation and download
   - Tab: `activity` - Activity log viewer
   - Tab: `users` - User login status toggle

4. **PlanManagement.vue** - SaaS plan management
   - Create/edit/delete plans
   - Set staff limits and duration

5. **DropdownSettings.vue** - Tenant field options management
   - Manage dropdown options for: Business Type, Lead Source, Division, Township, Product, Channel, Package
   - Add/edit/delete options per field type

6. **Auth Pages**
   - Welcome.vue - Landing page
   - Login.vue - Login form
   - Register.vue - Registration form

### Shared Components

- **Menu.vue** - Navigation menu component
  - Shows/hides menu items based on permissions
  - Active tab highlighting

---

## 🔌 API Endpoints (Mobile App)

### Base URL: `/api`

**Authentication:** Laravel Sanctum Bearer Token

#### 1. Login
```
POST /sale_admin_login
Body: { email, password, app_version }
Response: { uid, name, email, token, tenant_id }
```

#### 2. Dashboard Overview
```
GET /get_activity_overview_by_uid?uid={uid}&app_version=1.0
Response: { total_leads, contracted_leads, pending_followups }
```

#### 3. Lead List
```
GET /get_lead_list_by_uid?uid={uid}&app_version=1.0
Response: Array of leads (filtered by staff/owner)
```

#### 4. Dropdown Data
```
GET /get_sale_ddl_data
Response: { business_type[], lead_source[], division[], township[], product[], package[], channel[], status[] }
```

#### 5. Lead Detail
```
GET /get_activity_detail?uid={uid}&leadId={id}&app_version=1.0
Response: Single lead object
```

#### 6. Create/Update Lead
```
POST /post_lead_form_data
Body: { lid, uid, business_name, contact_number, email, source, division, township, address, status, amount, plan, package, discount, meeting_notes, next_step, ... }
Response: { status, message, data }
```

#### 7. Contracted Leads List
```
GET /get_contracted_lead_lists_by_uid?uid={uid}&app_version=1.0
Response: Array of contracted leads (contracted_date IS NOT NULL)
```

#### 8. Contracted Lead Detail
```
GET /get_contracted_detail?uid={uid}&leadId={id}&app_version=1.0
Response: Single contracted lead object
```

#### 9. Update Contract Info
```
POST /post_contracted_data
Body: { uid, profile_id, contracted_date, installation_appointment_date, customer_note, amount }
Response: { status, message }
```

### API Field Mappings

| API Field | Database Column |
|-----------|----------------|
| lid | id |
| uid | created_by |
| business_type | biz_type |
| contact_number | phone |
| email | contact_email |
| meeting_notes | meeting_note |
| profile_id | id |

---

## 🛠️ Key Controllers

### LeadController
- `dashboard()` - Dashboard with metrics, reports, and charts
- `index()` - Lead list with search and filters
- `create()` - Show create form
- `store()` - Create new lead
- `update()` - Update existing lead
- `export()` - Export leads to CSV
- `import()` - Import leads from CSV with smart column mapping
- `upload()` - Show upload interface

**Features:**
- Smart CSV import with flexible column name matching
- Duplicate detection by phone number
- Update existing leads option
- Comprehensive error reporting
- Staff-level filtering (staff see only their leads)

### UserController
- `index()` - User list with roles and plans
- `store()` - Create new user (with staff limit check)
- `update()` - Update user details
- `destroy()` - Soft delete user
- `toggleActive()` - Approve/deactivate user login

**Features:**
- Automatic tenant assignment for staff users
- Plan expiration calculation
- Staff limit enforcement
- Activity logging

### SalesAppController (API)
- All 9 mobile API endpoints
- Sanctum authentication
- Multi-tenant filtering
- Staff vs owner permission logic
- Field mapping between API and database

### TenantFieldController
- `index()` - Show dropdown settings page
- `store()` - Add new option
- `updateOption()` - Edit option
- `destroy()` - Delete option

**Features:**
- Tenant-specific options (uses owner user ID as tenant_id)
- 7 field types supported

### RoleController
- `store()` - Create new role
- `update()` - Update role name
- `syncPermissions()` - Assign permissions to role
- `storePermission()` - Create new permission

### PlanController
- `index()` - List all plans
- `store()` - Create new plan
- `update()` - Update plan details
- `destroy()` - Delete plan

### ProfileController
- `index()` - Show settings page
- `update()` - Update profile (name, email, password, avatar)
- `createBackup()` - Generate database backup (REMOVED - not implemented)
- `downloadBackup()` - Download backup file (REMOVED)
- `deleteBackup()` - Delete backup file (REMOVED)

---

## 🔐 Middleware

### InitializeTenancyByAuthUser
```php
- Checks if user is authenticated
- Skips for super admins (is_admin = true)
- Finds tenant by user->tenant_id
- Reconfigures 'tenant' connection to point to tenant's database
- Purges and reconnects 'tenant' connection
- Only affects 'tenant' connection, never touches 'mysql' or 'mysql_central'
```

### Other Middleware
- `auth` - Laravel authentication
- `auth:sanctum` - Sanctum API authentication
- `guest` - Redirect authenticated users
- `tenant` - Alias for InitializeTenancyByAuthUser

---

## 📁 File Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   └── SalesAppController.php (9 API endpoints)
│   │   ├── Auth/
│   │   │   └── AuthController.php (login, register, logout)
│   │   ├── LeadController.php (dashboard, CRUD, import/export)
│   │   ├── UserController.php (user management)
│   │   ├── RoleController.php (roles & permissions)
│   │   ├── TenantFieldController.php (dropdown options)
│   │   ├── PlanController.php (SaaS plans)
│   │   └── ProfileController.php (settings)
│   ├── Middleware/
│   │   └── InitializeTenancyByAuthUser.php (tenant switching)
│   └── Kernel.php (middleware registration)
├── Models/
│   ├── User.php (connection: mysql_central)
│   ├── Lead.php (connection: tenant)
│   ├── TenantFieldOption.php (connection: tenant)
│   ├── Tenant.php (connection: mysql_central)
│   ├── Plan.php (connection: mysql_central)
│   └── ActivityLog.php (connection: mysql_central)
└── Observers/
    └── UserObserver.php (creates tenant database on user creation)

resources/
├── js/
│   ├── Pages/
│   │   ├── Dashboard.vue (main dashboard with 4 tabs)
│   │   ├── UserManagement.vue (users, roles, permissions)
│   │   ├── Settings.vue (profile, backup, activity, user status)
│   │   ├── PlanManagement.vue (SaaS plans)
│   │   ├── DropdownSettings.vue (tenant field options)
│   │   └── Auth/ (Welcome, Login, Register)
│   └── Components/
│       └── Menu.vue (navigation menu)
└── css/
    ├── dashboard.css
    ├── usermanagement.css
    ├── settings.css
    ├── planmanagement.css
    └── dropdownsettings.css

database/
├── migrations/
│   ├── 2014_10_11_000000_create_plans_table.php
│   ├── 2014_10_12_000000_create_users_table.php
│   ├── 2019_09_15_000010_create_tenants_table.php
│   ├── 2024_01_01_000002_create_leads_table.php
│   ├── 2024_01_01_000003_create_tenant_field_options_table.php
│   ├── 2026_03_20_114647_create_permission_tables.php
│   ├── 2026_03_22_190314_create_activity_logs_table.php
│   └── tenant/
│       ├── 2024_01_01_000002_create_leads_table.php
│       ├── 2024_01_01_000003_create_tenant_field_options_table.php
│       └── 2026_04_06_000001_add_contracted_fields_to_leads_table.php (NEW)
└── seeders/
    ├── AdminSeeder.php
    ├── DemoUserSeeder.php
    └── LeadSeeder.php

routes/
├── web.php (web routes with auth + tenant middleware)
└── api.php (API routes with auth:sanctum + tenant middleware)

config/
├── database.php (3 connections: mysql, mysql_central, tenant)
├── sanctum.php (API authentication)
└── permission.php (Spatie permissions)
```

---

## 🔄 Key Workflows

### 1. User Registration & Tenant Creation

```
1. User fills registration form (Register.vue)
2. POST /register → AuthController::register()
3. User created in central DB with is_active = false
4. UserObserver::created() fires
5. Observer creates Tenant record with UUID
6. Observer creates new MySQL database (tenant_{uuid}_db)
7. Observer runs tenant migrations on new database
8. User assigned 'User' role
9. Redirect to login (awaiting admin approval)
```

### 2. Staff User Creation

```
1. Tenant owner clicks "Add User" (UserManagement.vue)
2. POST /users → UserController::store()
3. Check staff limit (plan->staff_limit)
4. Create user with tenant_id = owner's tenant_id
5. Assign 'Staff' role
6. Set is_active = false (requires approval)
7. Activity log created
8. User can login after owner approves (toggleActive)
```

### 3. Lead Creation (Web)

```
1. User navigates to Create tab (Dashboard.vue)
2. Fills multi-section form (8 sections)
3. POST /leads → LeadController::store()
4. Middleware switches to tenant database
5. Lead created with created_by = auth()->id()
6. UUID auto-generated
7. Redirect to leads list
```

### 4. Lead Creation (Mobile API)

```
1. Mobile app sends POST /post_lead_form_data
2. Sanctum validates Bearer token
3. Middleware switches to user's tenant database
4. SalesAppController maps API fields to DB columns
5. If lid = 0 → Create new lead
6. If lid = existing ID → Update lead
7. Return success response
```

### 5. CSV Import

```
1. User uploads CSV file (Dashboard.vue upload tab)
2. POST /leads/import → LeadController::import()
3. Parse CSV headers (flexible column name matching)
4. For each row:
   - Check if phone exists (duplicate detection)
   - If exists and update_existing = true → Update
   - If exists and update_existing = false → Skip
   - If not exists → Create new lead
5. Return import summary (created, updated, skipped, errors)
```

### 6. Permission Check Flow

```
1. User clicks menu item or button
2. Vue component calls can('permission_name')
3. Checks auth.user.permissions array
4. Show/hide UI element based on result
5. Backend also validates permissions via Spatie middleware
```

---

## 🎯 Business Logic

### Staff Limit Enforcement

```php
// In UserController::store()
$limit = $authUser->plan ? $authUser->plan->staff_limit : 5;
$currentStaffCount = User::where('tenant_id', $tenantId)->count();
if ($currentStaffCount >= $limit) {
    return error('Staff limit reached');
}
```

### Lead Visibility Rules

**Tenant Owner:**
- Sees all leads in their tenant database
- No filtering by created_by

**Staff User:**
- Sees only leads where created_by = their user ID
- Filtered in LeadController and SalesAppController

**Super Admin:**
- No tenant switching
- Can access all data across all tenants

### Plan Expiration

```php
// When plan assigned to user
$user->plan_expired_at = now()->addDays($plan->duration_in_days);

// In UI
$remainingDays = (new Date($user->plan_expired_at) - now()) / (1000*60*60*24);
if ($remainingDays <= 7) {
    // Show warning icon
}
```

---

## 🚀 Recent Implementations

### Sales API Backend (Completed)
- ✅ All 9 API endpoints implemented
- ✅ Sanctum authentication configured
- ✅ Multi-tenant database switching
- ✅ Staff vs owner permission logic
- ✅ Field mapping (API ↔ Database)
- ✅ Added contracted_date, installation_appointment_date, customer_note fields
- ✅ Migration created for new fields
- ✅ API testing guide created

### Dropdown Settings Page (Completed)
- ✅ TenantFields.vue created with sidebar menu
- ✅ 7 field types with icons
- ✅ Add/edit/delete options
- ✅ Tenant-specific storage

### User Management Improvements (Completed)
- ✅ Staff role badges hidden in list view
- ✅ Staff users have disabled action buttons
- ✅ Only Detail button works for staff users

---

## 📝 Important Notes

### Database Connection Rules
1. **NEVER** use `tenant` connection for User, Role, Permission, Plan, Tenant, ActivityLog models
2. **ALWAYS** use `mysql_central` for central tables
3. **ONLY** Lead and TenantFieldOption use `tenant` connection
4. Middleware switches `tenant` connection, never touches `mysql` or `mysql_central`

### Tenant ID Confusion
- `users.tenant_id` can be:
  - NULL (for super admin or tenant owner)
  - User's own ID (for tenant owner)
  - Owner's user ID (for staff users)
- `tenants.id` is a UUID string
- `tenant_field_options.tenant_id` is the owner's integer user ID (not UUID)

### Permission Naming Convention
- Menu access: `menu_{name}` (e.g., menu_dashboard, menu_lists)
- Submenu access: `submenu_{name}` (e.g., submenu_users, submenu_roles)
- Actions: `action_{name}` (e.g., action_upload_lead, action_download_csv)
- CRUD: `{action}_{resource}` (e.g., create_users, edit_users, delete_users)
- Settings: `setting_{name}` (e.g., setting_profile, setting_backup)
- Form sections: `section_{name}` (e.g., section_business_info, section_contact_info)

### CSV Import Column Mapping
The import supports flexible column names:
- `phone` / `phone_number` / `ph` / `contact_information` / `mobile` → phone
- `first_name` / `firstname` / `first name` → first_name
- `business_name` / `business name` / `company` → business_name
- `contact_email` / `email` / `e-mail` → contact_email
- And many more...

---

## 🔧 Configuration Files

### .env (Key Variables)
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pipeline (central database)
DB_USERNAME=root
DB_PASSWORD=

SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
```

### config/database.php
- 3 connections defined: mysql, mysql_central, tenant
- mysql and mysql_central point to same database
- tenant connection database is overridden at runtime

### config/sanctum.php
- Token-based API authentication
- No expiration by default
- Stateful domains for SPA

---

## 📚 Dependencies

### Backend (composer.json)
- laravel/framework: ^10.0
- laravel/sanctum: ^3.0
- spatie/laravel-permission: ^5.0
- inertiajs/inertia-laravel: ^0.6

### Frontend (package.json)
- vue: ^3.3
- @inertiajs/vue3: ^1.0
- vuetify: ^4.0
- sweetalert2: ^11.0
- axios: ^1.0

---

## 🎨 UI/UX Patterns

### Color Scheme
- Primary Green: #2ecc5e
- Background: #f3f4f6
- Text: #111827, #374151, #6b7280
- Borders: #e5e7eb
- Error Red: #ef4444
- Success Green: #10b981

### Button Styles
- `.btn-solid-green` - Primary action button
- `.btn-outline-green` - Secondary action button
- `.btn-edit` - Edit action (blue)
- `.btn-delete` - Delete action (red)
- `.btn-detail` - Detail view (gray)
- `.btn-toggle` - Activate/deactivate (green/red)

### Modal Pattern
- Custom modal backdrop with click-outside-to-close
- Modal card with header, body, footer
- Form groups with labels and error messages
- Save/Cancel buttons in footer

### Table Pattern
- `.data-table` - Striped table with hover effects
- `.action-td` - Action column with button group
- `.role-badge` - Colored badge for roles
- `.status-btn` - Status indicator (active/inactive)

---

## 🐛 Known Issues & Limitations

1. **Database Backup Feature** - Removed from ProfileController (not implemented)
2. **Tenant ID Inconsistency** - tenant_id can be UUID or integer depending on context
3. **No Soft Delete for Leads** - Leads use soft deletes but no UI to restore
4. **CSV Import Error Handling** - Limited to first 10 errors shown
5. **No Lead Assignment** - Staff users can't reassign leads to other staff
6. **No Lead Status Workflow** - No validation on status transitions
7. **No Email Notifications** - No email sent on user approval or plan expiration

---

## 🔮 Future Enhancements

1. **Lead Assignment** - Allow tenant owners to assign leads to staff
2. **Lead Status Workflow** - Define allowed status transitions
3. **Email Notifications** - User approval, plan expiration, lead updates
4. **Dashboard Charts** - Add visual charts for metrics
5. **Lead Comments** - Add comment system for lead collaboration
6. **File Attachments** - Allow file uploads for leads
7. **Advanced Filters** - Date range, multiple status, custom fields
8. **Export Customization** - Choose which columns to export
9. **Bulk Operations** - Bulk delete, bulk status update
10. **API Rate Limiting** - Implement rate limiting for mobile API

---

## 📖 Documentation Files

- `Sales-API-Documentation.md` - Mobile API endpoint documentation
- `API_TESTING_GUIDE.md` - Step-by-step API testing instructions
- `SALES_API_IMPLEMENTATION_SUMMARY.md` - Implementation details and status
- `CODEBASE_ANALYSIS.md` - This comprehensive analysis document

---

**Last Updated:** May 15, 2026  
**Version:** 1.0  
**Status:** ✅ Production Ready
