# Camera Permissions Guide

## How to Enable Camera Access for QR Code Scanning

The customer confirmation page requires camera access to scan QR codes. Here's how to enable camera permissions in different browsers:

---

## 🌐 Google Chrome / Microsoft Edge

### Desktop:

1. Click the **camera icon** (🎥) or **lock icon** (🔒) in the address bar (left side)
2. Find "Camera" in the dropdown menu
3. Select **"Allow"**
4. Refresh the page

### Alternative Method:

1. Go to `chrome://settings/content/camera` (Chrome) or `edge://settings/content/camera` (Edge)
2. Check that camera is not blocked
3. Add your site to the "Allow" list if needed

### Mobile (Android):

1. Go to **Settings** → **Apps** → **Chrome/Edge**
2. Tap **Permissions**
3. Enable **Camera**
4. Refresh the page in browser

---

## 🦊 Mozilla Firefox

### Desktop:

1. Click the **camera icon** or **lock icon** in the address bar
2. Click the **"X"** next to "Blocked Temporarily" if camera is blocked
3. Select **"Allow"** when prompted
4. Refresh the page

### Alternative Method:

1. Go to `about:preferences#privacy`
2. Scroll to **Permissions** → **Camera**
3. Click **Settings** next to Camera
4. Find your site and change to **Allow**

### Mobile (Android):

1. Go to **Settings** → **Apps** → **Firefox**
2. Tap **Permissions**
3. Enable **Camera**

---

## 🧭 Safari (macOS/iOS)

### macOS:

1. Click **Safari** in menu bar → **Settings for This Website**
2. Find **Camera** dropdown
3. Select **"Allow"**
4. Refresh the page

### System-Level (macOS):

1. Open **System Settings** → **Privacy & Security**
2. Click **Camera**
3. Enable checkbox for **Safari**

### iOS/iPadOS:

1. Go to **Settings** → **Safari**
2. Scroll to **Settings for Websites**
3. Tap **Camera**
4. Select **"Allow"** or **"Ask"**

---

## 🔒 HTTPS Requirement

**Important:** Most modern browsers require HTTPS (secure connection) to access the camera.

### For Development:

- `localhost` is treated as secure, so camera works on `http://localhost:5174`
- For testing on other devices on your network, you may need to:
  1. Use a tool like `ngrok` to create HTTPS tunnel
  2. Set up local SSL certificates
  3. Use browser flags (not recommended for production)

### For Production:

- **Always use HTTPS** in production
- Get an SSL certificate (free from Let's Encrypt)
- Configure your web server to use HTTPS

---

## 🐛 Troubleshooting

### Camera Not Working After Allowing Permission:

1. **Refresh the page** (F5 or Cmd+R)
2. **Clear browser cache** and try again
3. **Check if another app is using the camera**
   - Close video conferencing apps (Zoom, Teams, etc.)
   - Close other browser tabs using camera
4. **Restart the browser**
5. **Check system camera settings** (especially on macOS)

### "Camera Not Found" Error:

1. Verify your device has a camera
2. Check if camera is enabled in system settings
3. Try a different browser
4. Update your browser to the latest version

### Permission Prompt Not Appearing:

1. Check if you previously blocked camera access
2. Clear site permissions and try again:
   - Chrome: `chrome://settings/content/siteDetails?site=http://localhost:5174`
   - Firefox: Click lock icon → Clear permissions
   - Safari: Safari → Settings for This Website → Reset

### Still Not Working?

**Use the Manual Code Entry option:**

- Instead of scanning, customers can type the reservation code manually
- Format: `RES-123-1234567890`
- This works without camera access

---

## 📱 Mobile Browser Tips

### Best Practices:

1. **Use native browsers** (Chrome on Android, Safari on iOS)
2. **Hold phone steady** when scanning
3. **Ensure good lighting**
4. **Keep QR code in focus**
5. **Try landscape orientation** if having issues

### Common Mobile Issues:

- **Camera opens but doesn't scan**: Ensure QR code is well-lit and in focus
- **Permission denied**: Check app permissions in phone settings
- **Black screen**: Another app may be using the camera

---

## 🔐 Privacy & Security

### What We Access:

- **Camera feed only** - used for QR code scanning
- **No recording** - video is not saved or transmitted
- **Local processing** - QR code is decoded in your browser
- **No personal data** - only the reservation code is sent to server

### Your Privacy:

- Camera access can be revoked anytime
- Closing the scanner stops camera immediately
- No images or videos are stored
- Camera is only active when scanner is open

---

## 💡 Alternative: Manual Code Entry

If camera access is not available or preferred:

1. On the confirmation page, scroll to **"OR"** section
2. Enter your reservation code in the text field
3. Click **"Verify Code"**
4. View your reservation details

**Reservation code format:** `RES-{number}-{timestamp}`
Example: `RES-123-1710604800`

---

## 📞 Need Help?

If you continue to experience issues:

1. Try the manual code entry option
2. Contact restaurant staff for assistance
3. Use a different device or browser
4. Check the troubleshooting section above

---

## 🔧 For Developers

### Testing Camera Locally:

```bash
# Development server (localhost is treated as secure)
npm run dev

# Access from other devices (requires HTTPS)
# Option 1: Use ngrok
ngrok http 5174

# Option 2: Use Vite with HTTPS
npm run dev -- --https
```

### Browser Console Debugging:

```javascript
// Check if camera is available
navigator.mediaDevices
  .enumerateDevices()
  .then((devices) =>
    console.log(devices.filter((d) => d.kind === "videoinput")),
  );

// Check permissions
navigator.permissions
  .query({ name: "camera" })
  .then((result) => console.log("Camera permission:", result.state));
```

### Error Handling:

The app includes comprehensive error handling:

- Permission denied → Shows manual entry option
- Camera not found → Displays helpful error message
- Scanning failed → Allows retry or manual entry
