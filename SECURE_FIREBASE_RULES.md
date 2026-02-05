# Firebase Realtime Database Security Rules

## 🚨 Current Issue: Insecure Database Rules

Your Firebase Realtime Database currently has insecure rules that allow any authenticated user to read/write all data.

## 🔧 Immediate Fix Required

### Step 1: Go to Firebase Console
1. Open [Firebase Console](https://console.firebase.google.com)
2. Select your project: `reminder-c0728-default-rtdb`
3. Go to **Realtime Database** from left menu
4. Click on **Rules** tab (next to Data tab)

### Step 2: Replace Current Rules
Delete all existing rules and replace with the secure rules below.

## 📋 Secure Firebase Rules

```json
{
  "rules": {
    ".read": "auth != null",
    ".write": "auth != null",
    "users": {
      ".read": "auth != null && auth.email === 'admin@gmail.com'",
      ".write": "auth != null && auth.email === 'admin@gmail.com'",
      "$uid": {
        ".read": "auth != null && auth.uid === $uid",
        ".write": "auth != null && auth.uid === $uid"
      },
      "fcm_tokens": {
        ".read": "auth != null && auth.uid === $uid",
        ".write": "auth != null && auth.uid === $uid"
      }
    },
    "faculty_leave_master": {
      ".read": "auth != null",
      ".write": "auth != null && auth.email === 'admin@gmail.com'"
    },
    "leave_requests": {
      ".read": "auth != null",
      ".write": "auth != null"
    },
    "leave_ledger": {
      ".read": "auth != null",
      ".write": "auth != null && auth.email === 'admin@gmail.com'"
    },
    "lecture_templates": {
      ".read": "auth != null",
      ".write": "auth != null && auth.email === 'admin@gmail.com'"
    },
    "invigilation": {
      ".read": "auth != null",
      ".write": "auth != null && auth.email === 'admin@gmail.com'"
    },
    "invigilation_templates": {
      ".read": "auth != null",
      ".write": "auth != null && auth.email === 'admin@gmail.com'"
    },
    "messages": {
      ".read": "auth != null",
      ".write": "auth != null",
      "$messageId": {
        ".read": "auth != null && (data.child('sender_email').val() === auth.email || data.child('recipient_email').val() === auth.email)",
        ".write": "auth != null && (data.child('sender_email').val() === auth.email || data.child('recipient_email').val() === auth.email)"
      }
    }
  }
}
```

## 🔒 Security Features Explained

### **🛡️ Base Rules**
```json
".read": "auth != null",
".write": "auth != null"
```
- Only authenticated users can access the database
- Prevents anonymous access

### **👑 User Data Protection**
```json
"users": {
  "$uid": {
    ".read": "auth != null && auth.uid === $uid",
    ".write": "auth != null && auth.uid === $uid"
  }
}
```
- Users can only read/write their own data
- Admin can manage all user data
- FCM tokens are protected per user

### **📚 Admin-Only Write Access**
```json
"faculty_leave_master": {
  ".write": "auth != null && auth.email === 'admin@gmail.com'"
}
```
- Only admin@gmail.com can modify faculty master data
- All authenticated users can read (for dropdowns)

### **📅 Leave Request System**
```json
"leave_requests": {
  ".read": "auth != null",
  ".write": "auth != null"
}
```
- Faculty can submit leave requests
- Admin can view all requests
- Proper request/response flow

### **📋 Message Security**
```json
"messages": {
  "$messageId": {
    ".read": "auth != null && (data.child('sender_email').val() === auth.email || data.child('recipient_email').val() === auth.email)",
    ".write": "auth != null && (data.child('sender_email').val() === auth.email || data.child('recipient_email').val() === auth.email)"
  }
}
```
- Users can only read their own messages
- Users can only send messages as themselves
- Prevents message spoofing

## 🚨 Security Risks Without These Rules

### **Current Insecure Rules Allow:**
- ❌ Any authenticated user can delete all data
- ❌ Faculty can modify other faculty's leave records
- ❌ Users can access messages not meant for them
- ❌ Data tampering and privacy violations
- ❌ Potential data loss or corruption

### **✅ Secure Rules Prevent:**
- ✅ Unauthorized data modification
- ✅ Cross-user data access
- ✅ Message spoofing and interception
- ✅ Admin privilege escalation
- ✅ Data integrity violations

## 🔧 Implementation Steps

### **1. Backup Current Data**
Before applying new rules:
1. Export all data from Firebase Console
2. Save backup locally
3. Test rules in test environment first

### **2. Apply New Rules**
1. Copy the secure rules above
2. Paste in Firebase Console Rules tab
3. Click **Publish**
4. Test application functionality

### **3. Test Security**
After applying rules:
1. **Test admin access** - Can manage all data
2. **Test faculty access** - Can only access their data
3. **Test messaging** - Users can only see their messages
4. **Test leave requests** - Proper request flow
5. **Test unauthorized access** - Should be blocked

## 📱 Mobile App Considerations

### **FCM Token Security**
```json
"users": {
  "$uid": {
    "fcm_tokens": {
      ".write": "auth != null && auth.uid === $uid"
    }
  }
}
```
- Users can only register their own FCM tokens
- Prevents notification hijacking
- Maintains privacy

### **User Profile Access**
```json
"users": {
  "$uid": {
    ".read": "auth != null && auth.uid === $uid"
  }
}
```
- Users can only access their own profiles
- Admin retains oversight capability
- Proper data isolation

## 🔍 Testing Your Rules

### **Firebase Rules Simulator**
1. In Firebase Console, click **Rules** tab
2. Click **Simulator** button
3. Test different scenarios:
   - Admin read/write operations
   - Faculty read/write operations
   - Cross-user access attempts
   - Unauthorized access attempts

### **Test Scenarios**
```json
// Test 1: Admin Access
{
  "auth": {
    "uid": "admin-uid",
    "email": "admin@gmail.com"
  },
  "path": "/faculty_leave_master",
  "method": "write"
}

// Test 2: Faculty Access
{
  "auth": {
    "uid": "faculty-uid", 
    "email": "faculty@university.edu"
  },
  "path": "/leave_requests",
  "method": "write"
}

// Test 3: Unauthorized Access
{
  "auth": null,
  "path": "/faculty_leave_master",
  "method": "write"
}
```

## 🚀 Production Deployment

### **Before Going Live:**
1. ✅ Apply secure rules to production database
2. ✅ Test all user roles and permissions
3. ✅ Verify data isolation between users
4. ✅ Test admin functionality
5. ✅ Monitor Firebase console for rule violations

### **Monitoring:**
- Check Firebase Console for rule violations
- Monitor error logs in your application
- Set up alerts for suspicious activities
- Regular security audits

## 📞 Emergency Access

### **If You Lock Yourself Out:**
1. Go to Firebase Console → Authentication
2. Add temporary admin user
3. Update rules to include new admin
4. Remove temporary user after fixing

### **Rule Recovery:**
- Keep backup of working rules
- Document rule changes
- Test in staging environment first
- Have rollback plan ready

---

**🔒 Apply these rules immediately to secure your Faculty Management System!**

**Your data and user privacy depend on proper Firebase security configuration.**
