# IRCTC Website - Setup and Customization Guide

## 🚂 Overview
This is a complete IRCTC-style website with dynamic customization features. The website includes:
- Modern responsive navbar matching IRCTC design
- Dynamic carousel with customizable slides
- Service icons section
- Professional footer with social media links
- Admin panel for easy customization

## 📁 Files Created
1. **`irctc_website.php`** - Main website file
2. **`changing.php`** - Admin customization panel
3. **`setup_irctc_tables.php`** - Database setup script

## 🛠️ Setup Instructions

### Step 1: Database Setup
1. Run the database setup script first:
   ```
   http://localhost/RAILWAY/kaiadmin-lite-1.2.0/admin/setup_irctc_tables.php
   ```
   This will create the necessary tables for website customization.

### Step 2: View the Website
2. Access the main website:
   ```
   http://localhost/RAILWAY/kaiadmin-lite-1.2.0/admin/irctc_website.php
   ```

### Step 3: Customize the Website
3. Use the admin panel to customize colors, content, and carousel:
   ```
   http://localhost/RAILWAY/kaiadmin-lite-1.2.0/admin/changing.php
   ```

## 🎨 Customization Features

### Color Customization
- **Primary Color**: Navbar background, main buttons
- **Secondary Color**: Accent elements, "Daily Deals" button
- **Accent Color**: Links and special highlights

### Site Information
- Site title
- Contact phone number
- Contact email

### Carousel Management
- Add new slides with custom titles and descriptions
- Set custom images for each slide
- Configure button text and links
- Reorder slides with sort order
- Delete unwanted slides

## 🎯 Key Features

### Navbar (Based on IRCTC Design)
- **Top Header**: Logo, login buttons, daily deals, date/time, font controls
- **Main Navigation**: Home icon, service links with hover effects
- **Responsive Design**: Works on all screen sizes

### Carousel
- Full-width responsive carousel
- Custom slide content from database
- Smooth transitions and controls
- Mobile-optimized

### Services Section
- 10 service icons (Flights, Hotels, Rail Drishti, etc.)
- Hover animations
- Responsive grid layout

### Footer
- Social media icons with brand colors
- Multiple footer columns with links
- Payment method logos
- Copyright information

## 📱 Responsive Design
The website is fully responsive with breakpoints for:
- Desktop (1200px+)
- Laptop (992px+)
- Tablet (768px+)
- Mobile (576px and below)

## 🔧 Technical Details

### Database Tables
- `website_customization`: Stores color schemes and site information
- `carousel_slides`: Manages carousel content

### CSS Features
- CSS Custom Properties for dynamic theming
- Modern gradients and animations
- Smooth hover effects
- Mobile-first responsive design

### JavaScript Features
- Real-time date/time updates
- Font size controls
- Color preview updates
- Bootstrap carousel functionality

## 🎨 Color Scheme
Default IRCTC colors:
- **Primary**: #1e3a8a (IRCTC Blue)
- **Secondary**: #f97316 (IRCTC Orange)
- **Accent**: #059669 (Green)

## 📝 Usage Tips

1. **First Time Setup**: Always run `setup_irctc_tables.php` first
2. **Color Changes**: Use the color picker in `changing.php` for instant preview
3. **Carousel Images**: Use high-quality images (1920x800px recommended)
4. **Mobile Testing**: Test on different screen sizes for best results

## 🚀 Next Steps
- Add more service sections
- Implement booking functionality
- Add user authentication
- Create admin dashboard
- Add more customization options

## 📞 Support
For any issues or customizations, refer to the admin panel at `changing.php` where you can:
- Preview changes in real-time
- Reset to default settings
- Export/import configurations

---
**Built with**: PHP, MySQL, Bootstrap 5, Font Awesome
**Compatible with**: XAMPP, WAMP, LAMP servers
