# Visitor Registration System - Implementation Guide

## Overview
You now have a complete visitor registration system with the following features:
- Photo upload and webcam capture
- Visitor form registration
- Photo preview
- Real-time visitor list with status tracking
- Check-in/check-out functionality

## Features Implemented

### 1. **Photo Capture & Upload**
   - **Upload Option**: Users can upload JPG, PNG, or BMP photos (max 5MB)
   - **Webcam Capture**: Real-time photo capture using browser's getUserMedia API
   - **Drag & Drop**: Images can be dragged directly onto the upload area
   - **Photo Preview**: Instant preview of selected/captured photo
   - **Remove Option**: Users can remove and replace photos

### 2. **Visitor Registration Form**
   - Full Name (required)
   - Phone Number (required)
   - Person to Visit (dropdown - linked to employees)
   - Purpose of Visit (Meeting, Delivery, Maintenance, Business, Other)
   - Remarks (optional)
   - Photo (optional)

### 3. **Real-time Visitor List**
   - Displays all today's visitors
   - Shows photo thumbnail
   - Visitor details (name, phone, person to visit, purpose)
   - Check-in and Check-out times
   - Duration calculation
   - Quick actions:
     - Check Out button for on-site visitors
     - View button for details

### 4. **Database Integration**
   - Visitors are stored in `visitors` table
   - Automatic date and time-in on registration
   - Time-out recorded on checkout
   - Photo path stored for reference
   - Employee relationship for "person to visit"

## Technical Setup

### Database Changes
- Added `remarks` column to `visitors` table via migration
- Visitor records include:
  - full_name
  - phone
  - person_to_visit (references employees)
  - purpose
  - remarks
  - date
  - time_in (HH:mm:ss format)
  - time_out (HH:mm:ss format)
  - photo_path

### Files Modified/Created

1. **Controller**: `app/Http/Controllers/VisitorController.php`
   - `register()` - Display registration form
   - `store()` - Save new visitor
   - `checkOut()` - Record visitor checkout
   - `getTodaysVisitors()` - API endpoint for visitor list

2. **Model**: `app/Models/Visitor.php`
   - Employee relationship
   - Duration calculation
   - Photo URL accessor
   - Status helpers

3. **View**: `resources/views/visitor/register.blade.php`
   - Complete registration form
   - Photo capture interface
   - Webcam modal
   - Live visitor list table
   - JavaScript handling

4. **Routes**: `routes/web.php`
   - Added API endpoint `/visitor` for fetching today's visitors

5. **Layout**: `resources/views/layouts/app.blade.php`
   - Added CSRF token meta tag

6. **Migration**: `database/migrations/2025_01_20_000000_add_remarks_to_visitors_table.php`
   - Added remarks column

## How to Use

### Register a Visitor
1. Navigate to `/visitor/register`
2. Fill in the visitor's details
3. Add a photo either by:
   - Uploading a file
   - Capturing from webcam
4. Click "Complete Registration"

### Check Out a Visitor
1. In the "Recent Visitor Records" table
2. Click the checkout button (sign-out icon) for on-site visitors
3. Confirm the checkout action

### View Visitor List
- Visitors are auto-loaded every 30 seconds
- Scroll down to see all today's visitors
- Photos display as thumbnails
- Duration is calculated automatically

## Hardware Integration Notes

For your RFID and Biometric hardware:

### RFID Card Reader (HOBA 13.56MHZ/125KHZ)
- Can be integrated to auto-populate visitor data
- Suggestion: Create a separate endpoint to receive RFID data
- The system will auto-register visitors with captured fingerprints

### Biometric Fingerprint Scanner (QBYYY ZK9500)
- Can capture fingerprints for additional security
- Suggest adding a `fingerprint_path` column to visitors table
- Store fingerprint scan image similar to photos

### Integration Example (for future implementation)
```php
// In a new VisitorBiometricController
public function registerWithBiometric(Request $request)
{
    // RFID data comes in with visitor info
    $visitor = Visitor::create([
        'full_name' => $request->rfid_name,
        'phone' => $request->rfid_phone,
        'person_to_visit' => $request->person_to_visit,
        'purpose' => $request->purpose,
        'date' => today(),
        'time_in' => now()->format('H:i:s'),
        'photo_path' => $request->photo, // From camera
        'fingerprint_path' => $request->fingerprint, // From biometric
    ]);
    
    return response()->json(['success' => true, 'visitor' => $visitor]);
}
```

## File Storage
- Photos are stored in: `storage/app/public/visitors/`
- Accessible via: `/storage/visitors/filename.jpg`
- Max file size: 5MB
- Supported formats: JPG, PNG, BMP

## Browser Compatibility
- Webcam capture requires:
  - Chrome 53+
  - Firefox 36+
  - Safari 14.1+
  - Edge 79+
- HTTPS or localhost required for camera access

## Next Steps

1. **Test the system**:
   ```bash
   php artisan serve
   ```
   Visit: `http://localhost:8000/visitor/register`

2. **Add employees** (if not already done):
   - Create some employees to select as "Person to Visit"

3. **Integrate hardware**:
   - Set up RFID reader to send data to your system
   - Configure fingerprint scanner API endpoints

4. **Customize**:
   - Add more purpose categories
   - Add visitor approval workflow
   - Generate visitor badges/passes
   - Send notifications to person being visited

## Troubleshooting

### Photos not displaying
- Check if symlink exists: `php artisan storage:link`
- Verify uploads directory is writable: `storage/app/public/visitors/`

### Webcam not working
- Ensure HTTPS or localhost
- Check browser permissions for camera
- Test in browser developer console

### Visitors not loading
- Check browser console for errors
- Verify database has employees created
- Check if `getTodaysVisitors()` route is accessible

## Performance Tips
- Visitor list auto-refreshes every 30 seconds
- Consider pagination if you have 100+ daily visitors
- Images are stored locally; consider CDN for large deployments
- Compress photos before storage for better performance

---

Your visitor management system is now ready to use! The hardware integration points are prepared for future enhancements.
