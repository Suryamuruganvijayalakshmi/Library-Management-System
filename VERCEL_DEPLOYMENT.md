# Vercel Deployment Guide

This Library Management System is now configured as a web application ready for deployment on Vercel.

## Prerequisites

1. **Vercel Account** - Sign up at https://vercel.com
2. **GitHub Repository** - Your code is already pushed (✓ done)
3. **Git installed** on your machine

## Deployment Steps

### Option 1: Deploy via Vercel CLI (Fastest)

```bash
# Install Vercel CLI globally
npm install -g vercel

# Navigate to your project directory
cd "path/to/Library ,anagemnt"

# Login to Vercel
vercel login

# Deploy to Vercel
vercel

# For production deployment
vercel --prod
```

### Option 2: Deploy via GitHub (Recommended)

1. **Go to Vercel Dashboard**
   - Visit https://vercel.com/dashboard
   - Click "Add New" → "Project"

2. **Import GitHub Repository**
   - Select "Import Git Repository"
   - Select your GitHub account
   - Search for "Library-Management-System"
   - Click "Import"

3. **Configure Project**
   - **Framework**: Node.js
   - **Build Command**: `npm install`
   - **Output Directory**: (leave blank)
   - **Environment Variables**: (skip for now)

4. **Deploy**
   - Click "Deploy"
   - Wait for deployment to complete

## What Gets Deployed

- ✅ Static files (HTML, CSS, JavaScript)
- ✅ Node.js/Express backend API
- ✅ JSON-based data storage (in `/data` folder)
- ✅ All API endpoints

## Data Storage Note

Currently, the application uses **JSON files** for data storage (located in `/data` directory). This works on Vercel, but has limitations:
- Data doesn't persist across serverless function resets
- Not ideal for production with multiple concurrent users

### For Production, Consider:

**MongoDB** (Recommended - Free tier available)
- Sign up at https://mongodb.com
- Get connection string
- Install MongoDB driver: `npm install mongoose`

**PostgreSQL**
- Use Railway, Render, or Heroku
- Install pg driver: `npm install pg`

**Firebase Realtime Database**
- Google's solution
- Install firebase: `npm install firebase-admin`

## Environment Variables (Optional)

Create a `.env` file for sensitive data:

```env
NODE_ENV=production
SESSION_SECRET=your-secret-key-here
DATABASE_URL=your-database-url
```

## Testing Locally

Before deploying, test locally:

```bash
# Install dependencies
npm install

# Start the development server
npm run dev

# Visit http://localhost:3000
```

## Troubleshooting

### Build fails
- Make sure `package.json` has all required dependencies
- Check Node.js version (18.x recommended)

### Application not working after deployment
- Check Vercel logs: Go to your project dashboard → "Logs" → "Function Logs"
- Verify API endpoints are accessible

### Data not persisting
- This is expected with JSON file storage on serverless
- Consider migrating to a proper database

## Next Steps

1. **Deploy to Vercel** using one of the methods above
2. **Share your live URL** (Vercel will provide it)
3. **Invite users** to access the web application
4. **Consider database migration** for production use

## Support

For Vercel documentation: https://vercel.com/docs
For Node.js/Express: https://expressjs.com/

---

**Your GitHub Repository**: https://github.com/Suryamuruganvijayalakshmi/Library-Management-System.git
