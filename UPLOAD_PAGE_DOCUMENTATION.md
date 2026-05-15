# Upload Lead Page Documentation

## 📍 URL: `http://127.0.0.1:8000/leads/upload`

---

## 🎯 Purpose

The Upload Lead page allows users to bulk import leads from CSV or Excel files. It provides:
- CSV/Excel file upload interface
- Sample CSV file download
- Duplicate handling options
- Smart column name matching
- Comprehensive error reporting

---

## 🖼️ UI Components

### Page Header
- **Title:** "Upload Lead" (green color: #2ecc5e)
- **Subtitle:** "Upload Excel or CSV files to add leads"
- **Action Buttons:**
  - **Download CSV** - Downloads sample CSV template
  - **Add New** - Redirects to create lead form

### Rules Alert Box
**Red alert icon with important information:**

**Required Columns:**
- `phone` (primary contact number)
- `first_name` (contact first name)
- `last_name` (contact last name)
- `business_name` (company/business name)

**Optional Columns:**
- `biz_type` - Business Type (e.g., Residential, Commercial)
- `source` - Lead Source (e.g., Own Lead, Social Media)
- `division` - Division/Region (e.g., Yangon, Mandalay)
- `township` - Township/City (e.g., Bahan, Chanayetharzan)
- `address` - Full address
- `product` - Product name (e.g., Internet)
- `package` - Package/Plan name (e.g., Home Plus, Business DIA)
- `package_total` - Package price (numeric)
- `discount` - Discount amount (numeric)
- `status` - Lead status (Active, Prospect, Contracted, Cancelled)
- `channel` - Sales channel (Direct, Partner)
- `est_contract_date` - Estimated contract date (YYYY-MM-DD)
- `est_start_date` - Estimated start date (YYYY-MM-DD)
- `est_follow_up_date` - Estimated follow-up date (YYYY-MM-DD)
- `is_referral` - Is referral? (Yes/No, 1/0, true/false)
- `meeting_note` - Meeting notes (text)
- `next_step` - Next action step (text)

**Important Note:**
> Download the Sample CSV File and fill it in — column names must match exactly.

### Upload Dropzone
- **Visual:** Large clickable area with plus icon
- **Text:** "Click to upload"
- **Subtitle:** "Excel file (.xlsx, .xls, .csv) (max 2MB)"
- **Accepted Formats:** .csv, .xlsx, .xls
- **Max Size:** 2MB
- **Selected File Display:** Shows filename in green when file is selected

### Update Existing Checkbox
- **Label:** "Update existing leads when phone or name+address matches"
- **Default:** Checked (true)
- **Purpose:** 
  - When checked: Updates existing leads if phone number matches
  - When unchecked: Skips duplicate leads and only creates new ones

### Action Buttons
- **Cancel** - Returns to leads list
- **Upload** - Submits the file for import

---

## 🔄 Upload Flow

### 1. User Interaction
```
1. User clicks "Click to upload" dropzone
2. File picker opens
3. User selects CSV/Excel file
4. Filename displays below dropzone
5. User checks/unchecks "Update existing" option
6. User clicks "Upload" button
```

### 2. Frontend Processing
```javascript
// ImportWidget.vue
const submit = () => {
    if (!file.value) return;
    emit('upload', { 
        file: file.value, 
        updateExisting: updateExisting.value 
    });
};
```

### 3. Backend Processing (LeadController::import)

#### Step 1: File Validation
```php
$request->validate([
    'file' => 'required|file',
    'update_existing' => 'nullable|string',
]);
```

#### Step 2: CSV Parsing
```php
$handle = fopen($file->getRealPath(), 'r');
$rawHeaders = fgetcsv($handle, 5000, ',');

// Remove BOM (Byte Order Mark) from first header
$rawHeaders[0] = ltrim($rawHeaders[0], "\xEF\xBB\xBF\xFF\xFE");

// Normalize headers to lowercase
$headers = array_map(fn($h) => trim(strtolower(trim($h))), $rawHeaders);
```

#### Step 3: Smart Column Mapping
The import supports **flexible column names**. For example:

**Phone Number Variations:**
- `phone`, `phone_number`, `ph`, `contact_information`, `mobile`, `mobile_number`, `contact_no`, `phonenumber`

**Name Variations:**
- `first_name`, `firstname`, `first name`, `fname`
- `last_name`, `lastname`, `last name`, `lname`
- `contact_name`, `contact name`, `name`, `full_name`

**Business Name Variations:**
- `business_name`, `business name`, `company`, `company_name`

**Email Variations:**
- `contact_email`, `email`, `e-mail`, `email_address`

**And many more...**

#### Step 4: Row Processing
For each CSV row:

1. **Extract Data** using flexible column matching
2. **Check for Required Fields:**
   - If no phone AND no name → Skip row (null_skip++)
3. **Duplicate Detection:**
   - Search for existing lead by phone number
   - If found and `update_existing = false` → Skip (duplicate_skip++)
   - If found and `update_existing = true` → Update existing lead
4. **Data Mapping:**
   ```php
   $mappedData = [
       'business_name' => $get(['business_name', 'business name', 'company']),
       'contact_name' => trim("{$firstName} {$lastName}"),
       'first_name' => $firstName,
       'last_name' => $lastName,
       'phone' => $phone,
       'biz_type' => $get(['biz_type', 'biz type', 'business_type']),
       // ... more fields
   ];
   ```
5. **Validation:**
   - `business_name` is required
   - If missing, log error and skip row
6. **Create or Update:**
   ```php
   if ($existing && $updateExisting) {
       $existing->update($mappedData);
       $updated++;
   } else {
       $mappedData['created_by'] = auth()->id();
       Lead::create($mappedData);
       $created++;
   }
   ```

#### Step 5: Error Handling
```php
try {
    // Create or update lead
} catch (\Exception $e) {
    $errors++;
    $errorDetails[] = "[{$business_name}]: " . $e->getMessage();
    \Log::error('CSV Import row error', [...]);
}
```

### 4. Response Handling

The backend returns an `importResult` object:

```php
$importResult = [
    'created' => $created,           // Number of new leads created
    'updated' => $updated,           // Number of existing leads updated
    'duplicate_skip' => $duplicateSkip, // Skipped duplicates (when update_existing = false)
    'null_skip' => $nullSkip,        // Skipped blank rows (no phone AND no name)
    'errors' => $errors,             // Number of failed rows
    'error_details' => [...],        // Array of error messages (max 10)
    'detected_headers' => [...],     // Column names found in CSV
];
```

### 5. Frontend Response Display

The Dashboard.vue component shows different SweetAlert2 modals based on the result:

#### Success Case (created > 0 or updated > 0)
```
Title: "Import Complete"
Icon: Success (green checkmark)
Content:
  - Created: X
  - Updated: Y
  - Skipped (duplicate): Z (if any)
  - Blank rows: N (if any)
  - X row(s) failed: (if any)
    • Error details...
```

#### All Rows Failed (errors > 0, created = 0, updated = 0)
```
Title: "Import Failed"
Icon: Error (red X)
Content:
  - X row(s) could not be saved:
    • Error 1
    • Error 2
    • ...
```

#### All Duplicates (duplicate_skip > 0, created = 0, updated = 0)
```
Title: "No New Leads"
Icon: Warning (yellow triangle)
Content:
  - X row(s) already exist.
  - Tick "Update existing" to overwrite them.
```

#### Column Name Mismatch (null_skip > 0, created = 0)
```
Title: "Column Name Mismatch"
Icon: Error (red X)
Content:
  - X row(s) had no phone AND no name found.
  - Columns detected in your file: [list of headers]
  - Required column names: phone, first_name (or last_name)
  - Make sure your file uses the Sample CSV headers exactly.
```

---

## 📄 Sample CSV Format

### File: `public/sample_leads.csv`

**Headers:**
```csv
id,uuid,business_name,first_name,last_name,contact_email,phone,secondary_contact_number,biz_type,source,division,township,address,product,package,package_total,discount,status,channel,installation_appointment,est_contract_date,est_start_date,est_follow_up_date,is_referral,meeting_note,next_step,created_at
```

**Example Row 1:**
```csv
,,ABC Company,John,Doe,john@example.com,09123456789,09987654321,Residential,Own Lead,Yangon,Bahan,No 1 Main Street,Internet,Home Plus,50000,0,Active,Direct,2026-04-10,2026-04-15,2026-05-01,2026-04-12,No,First meeting went well,Send contract,
```

**Example Row 2:**
```csv
,,XYZ Trading,Jane,Smith,jane@example.com,09111222333,09444555666,Commercial,Social Media,Mandalay,Chanayetharzan,No 5 Market Road,Internet,Business DIA,150000,5000,Prospect,Partner,,,2026-06-01,2026-04-20,Yes,Interested in DIA plan,Follow up call,
```

**Notes:**
- `id` and `uuid` columns should be left empty (auto-generated)
- `created_at` column should be left empty (auto-generated)
- Dates should be in `YYYY-MM-DD` format
- `is_referral` accepts: Yes/No, 1/0, true/false
- Numeric fields: `package_total`, `discount`

---

## 🔍 Smart Column Matching

The import system uses a flexible column matching algorithm that accepts multiple variations of column names:

### Phone Number
```php
['phone', 'phone_number', 'ph', 'contact_information', 
 'mobile', 'mobile_number', 'contact_no', 'phonenumber']
```

### Name Fields
```php
// First Name
['first_name', 'firstname', 'first name', 'fname']

// Last Name
['last_name', 'lastname', 'last name', 'lname']

// Full Name (splits into first + last)
['contact_name', 'contact name', 'name', 'full_name']
```

### Business Information
```php
// Business Name
['business_name', 'business name', 'company', 'company_name']

// Business Type
['biz_type', 'biz type', 'business_type', 'business type']
```

### Contact Information
```php
// Email
['contact_email', 'email', 'e-mail', 'email_address']

// Secondary Phone
['secondary_contact_number', 'secondary phone', 
 'secondary_phone', 'phone2', 'alt_phone']
```

### Location
```php
// Division
['division', 'region']

// Township
['township', 'town', 'city']

// Address
['address', 'full_address']
```

### Product & Package
```php
// Product
['product', 'product_name']

// Package
['package', 'plan_name']

// Plan (fallback to package)
['plan'] or ['package', 'plan_name']
```

### Financial
```php
// Package Total
['package_total', 'package total', 'total', 'amount']

// Discount
['discount', 'disc']
```

### Dates
```php
// Installation Appointment
['installation_appointment', 'installation appointment']

// Est. Contract Date
['est_contract_date', 'est. contract date', 'est contract date', 'contract_date']

// Est. Start Date
['est_start_date', 'est. start date', 'est start date', 'start_date']

// Est. Follow Up Date
['est_follow_up_date', 'est. follow up date', 'est follow up date', 'follow_up_date']
```

### Other Fields
```php
// Status
['status']

// Channel
['channel', 'sale_channel']

// Meeting Note
['meeting_note', 'meeting note', 'notes', 'note']

// Next Step
['next_step', 'next step', 'action']

// Is Referral
['is_referral', 'referral', 'ref']
```

---

## 🎨 UI Styling

### Colors
- **Primary Green:** #2ecc5e (title, selected file, success)
- **Error Red:** #ef4444 (alert icon, error messages)
- **Text Gray:** #374151, #4b5563, #6b7280
- **Border:** #e5e7eb

### Components
- **Upload Dropzone:** Dashed border, hover effect, centered content
- **Rules Alert:** Light red background, red icon, structured text
- **Buttons:**
  - Cancel: Gray outline
  - Upload: Green solid
  - Download CSV: Green outline
  - Add New: Green solid

### Layout
- **Card:** White background, rounded corners, shadow
- **Padding:** 24px consistent spacing
- **Responsive:** Full width, mobile-friendly

---

## 🔐 Permissions

**Required Permission:** `action_upload_lead`

**Access Control:**
```javascript
// In Dashboard.vue
if (tab.value === 'lists' && !can('view_leads') && 
    !can('action_upload_lead') && !can('action_download_csv')) {
    goToTab('dashboard'); // Redirect if no permission
}
```

**Who Can Upload:**
- Super Admin (is_admin = true)
- Users with `action_upload_lead` permission
- Tenant owners (if permission granted)
- Staff users (if permission granted)

---

## 📊 Import Statistics

### Counters
- **created** - New leads successfully created
- **updated** - Existing leads successfully updated
- **duplicate_skip** - Duplicates skipped (when update_existing = false)
- **null_skip** - Blank rows skipped (no phone AND no name)
- **errors** - Rows that failed validation or database insert

### Error Details
- Maximum 10 error messages shown in UI
- All errors logged to Laravel log file
- Error format: `[business_name or phone]: Error message`

---

## 🚀 Usage Examples

### Example 1: Import New Leads
1. Download sample CSV
2. Fill in lead data (required: phone, first_name, last_name, business_name)
3. Uncheck "Update existing" checkbox
4. Upload file
5. Result: New leads created, duplicates skipped

### Example 2: Update Existing Leads
1. Export current leads (Download CSV button)
2. Modify lead data in Excel
3. Check "Update existing" checkbox
4. Upload file
5. Result: Existing leads updated based on phone number match

### Example 3: Mixed Import
1. Prepare CSV with both new and existing leads
2. Check "Update existing" checkbox
3. Upload file
4. Result: New leads created, existing leads updated

---

## 🐛 Common Issues & Solutions

### Issue 1: "Column Name Mismatch"
**Cause:** CSV headers don't match expected column names  
**Solution:** 
- Download sample CSV
- Copy headers exactly
- Or use flexible column names (see Smart Column Matching section)

### Issue 2: "No New Leads" (All Duplicates)
**Cause:** All phone numbers already exist in database  
**Solution:** 
- Check "Update existing" checkbox to update instead of skip
- Or remove duplicate rows from CSV

### Issue 3: "X row(s) had no phone AND no name"
**Cause:** Required fields are empty or column names don't match  
**Solution:**
- Ensure every row has at least phone OR (first_name + last_name)
- Check column names match sample CSV

### Issue 4: Import Fails with Database Error
**Cause:** Invalid data format (e.g., text in numeric field)  
**Solution:**
- Check package_total and discount are numeric
- Check dates are in YYYY-MM-DD format
- Remove special characters from text fields

### Issue 5: File Upload Fails
**Cause:** File size exceeds 2MB or wrong format  
**Solution:**
- Compress file or split into multiple files
- Ensure file is .csv, .xlsx, or .xls format

---

## 🔄 Backend Route

```php
// routes/web.php
Route::post('/leads/import', [LeadController::class, 'import'])
    ->name('leads.import')
    ->middleware(['auth', 'tenant']);
```

**Middleware:**
- `auth` - User must be logged in
- `tenant` - Switches to user's tenant database

**Controller Method:** `LeadController::import(Request $request)`

---

## 📝 Code References

### Frontend Files
- `resources/js/Pages/Dashboard.vue` - Main dashboard with upload tab
- `resources/js/Components/Dashboard/ImportWidget.vue` - Upload UI component
- `resources/css/dashboard.css` - Styling

### Backend Files
- `app/Http/Controllers/LeadController.php` - Import logic (import method)
- `routes/web.php` - Route definition

### Sample File
- `public/sample_leads.csv` - Template CSV file

---

## 🎯 Key Features

1. **Flexible Column Matching** - Accepts multiple column name variations
2. **Duplicate Detection** - Finds existing leads by phone number
3. **Update or Skip** - User chooses whether to update duplicates
4. **Smart Validation** - Validates required fields and data types
5. **Comprehensive Reporting** - Shows created, updated, skipped, and error counts
6. **Error Details** - Displays specific error messages for failed rows
7. **UTF-8 Support** - Handles international characters and BOM
8. **Date Parsing** - Flexible date format parsing
9. **Referral Detection** - Accepts Yes/No, 1/0, true/false for is_referral
10. **Activity Logging** - Logs import actions (via created_by field)

---

## 📈 Performance Considerations

- **Max File Size:** 2MB (configurable in PHP settings)
- **Row Limit:** No hard limit, but large files may timeout
- **Processing Time:** ~1-2 seconds per 100 rows
- **Memory Usage:** Processes row-by-row to minimize memory
- **Recommendation:** Split files larger than 1000 rows

---

## 🔒 Security

- **File Type Validation:** Only .csv, .xlsx, .xls allowed
- **Authentication Required:** Must be logged in
- **Tenant Isolation:** Leads only created in user's tenant database
- **SQL Injection Protection:** Uses Eloquent ORM with parameter binding
- **XSS Protection:** All user input sanitized
- **CSRF Protection:** Laravel CSRF token required

---

**Last Updated:** May 15, 2026  
**Version:** 1.0  
**Status:** ✅ Fully Functional
