# Visitor Registration - Quick Start Guide

## 🚀 Get Started in 3 Steps

### Step 1: Run Setup (One Time)
```bash
cd C:\xampp\htdocs\attendance_app
php artisan migrate
php artisan storage:link
```

Or run the batch file:
```bash
setup-visitor-system.bat
```

### Step 2: Add Some Employees
Before registering visitors, you need employees in the system. Add them via the user management section or manually:

```bash
php artisan tinker
# Then run:
\App\Models\Employee::create([
    'first_name' => 'John',
    'last_name' => 'Smith',
    'department' => 'Management',
    'email' => 'john.smith@company.com'
]);
```

### Step 3: Start Registering Visitors
Visit: **http://localhost:8000/visitor/register**

## 📋 Registration Form Fields

| Field | Required | Notes |
|-------|----------|-------|
| Full Name | ✓ | Visitor's complete name |
| Phone | ✓ | Contact number |
| Person to Visit | ✓ | Select from employee list |
| Purpose | ✓ | Meeting, Delivery, etc. |
| Remarks | - | Optional notes |
| Photo | - | Upload or capture from webcam |

## 📸 Photo Options

### Upload Photo
1. Click "Upload Photo" button
2. Select JPG, PNG, or BMP file
3. Or drag & drop image onto the placeholder
4. Max file size: 5MB

### Capture with Webcam
1. Click "Capture with Webcam"
2. Allow camera access in browser
3. Click "Capture" to take photo
4. Photo appears in preview

### Remove Photo
- Click "Remove Photo" to delete and reselect

## ✅ How to Complete Registration

1. Fill all required fields
2. (Optional) Add a photo
3. Click "Complete Registration"
4. Visitor appears in the table below

## 👥 Manage Visitors

### Check Out a Visitor
- Click the "Check Out" button next to their name
- Confirm the action
- Time-out is recorded automatically

### View Visitor Info
- Click the "View" button to see more details
- Photo displays at larger size
- All details visible

## 📊 Visitor Dashboard

The table shows:
- **Photo** - Thumbnail of visitor's photo (if available)
- **Name** - Visitor's full name
- **Phone** - Contact number
- **Person to Visit** - Which employee they're visiting
- **Purpose** - Reason for visit
- **Check In** - Time visitor arrived (HH:MM AM/PM)
- **Check Out** - Time visitor left (-- if still on-site)
- **Duration** - How long they've been on-site
- **Action** - Quick buttons for checkout/view

## 🎮 Keyboard Shortcuts (Optional)

While using the webcam modal:
- **Esc** - Close camera and cancel
- **Space** - Capture photo (when focused)

## 🔍 Troubleshooting

### Webcam Not Working
**Problem**: "Unable to access camera"
- Ensure HTTPS or localhost
- Check browser camera permissions
- Grant access when browser asks

**Solution**:
1. In browser settings, allow camera access
2. Refresh the page
3. Try again

### Photos Not Showing
**Problem**: Photo uploads but doesn't display
- Check storage directory exists: `storage/app/public/visitors/`
- Run: `php artisan storage:link`

**Solution**:
```bash
php artisan storage:link
php artisan cache:clear
```

### Employee List Empty
**Problem**: No employees in "Person to Visit" dropdown

**Solution**: Add employees via user management or:
```bash
php artisan tinker
\App\Models\Employee::create([...])
```

## 🛠️ Admin Functions

### Export Visitor Records
Navigate to: `/attendance/visitor/export`

### View Full Visitor Log
Navigate to: `/attendance/visitor`

### Manage Employees
Navigate to: `/users`

## 🔐 Security Notes

- CSRF protection enabled for form submissions
- File upload validation for photos
- Database constraints prevent invalid data
- All timestamps recorded automatically

## 📁 File Locations

- **Visitor Photos**: `storage/app/public/visitors/`
- **Accessible Via**: `/storage/visitors/filename.jpg`
- **Web Root**: `public/`

## ⚙️ Configuration

Edit these if needed:
- **Max photo size**: 5MB (in VisitorController validation)
- **Refresh interval**: 30 seconds (in register.blade.php)
- **Purposes**: Meeting, Delivery, Maintenance, Business, Other

## 📚 More Information

- **Full Guide**: See `VISITOR_REGISTRATION_GUIDE.md`
- **System Summary**: See `VISITOR_SYSTEM_SUMMARY.md`
- **API Endpoints**: Check `routes/web.php`

## 🆘 Get Help

If you encounter issues:
1. Check browser console (F12) for JavaScript errors
2. Check Laravel logs: `storage/logs/`
3. Verify database: `php artisan tinker`
4. Check file permissions: `storage/app/public/visitors/` should be writable

## 📞 Contact

For hardware integration questions or custom features, refer to:
- `VISITOR_REGISTRATION_GUIDE.md` - Hardware integration section

---

**You're all set!** Start registering visitors now! 🎉
