# Iyra Motors WordPress theme

This is an independent WordPress theme. It uses WordPress/PHP and your WordPress database for inventory and customer inquiries, with a React interface. It is not Dealer Inspire and does not include their licensed software, inventory services, credit approvals, chat, or CRM connections.

## Install

1. Use a WordPress host with PHP 8.0+ and WordPress 6.0+. Cloudflare's static hosting cannot execute this PHP theme. You may use Cloudflare in front of your WordPress host.
2. Upload `iyra-motors-wordpress.zip` in Appearance → Themes → Add New → Upload Theme, then activate it.
3. In Settings → Permalinks, select Post name and save once.
4. Open Appearance → Iyra Motors. Add the dealership phone, email, address, hours, and the monitored email address for inquiry notifications.
5. Add actual cars under Vehicles. Complete the details, description, and featured photo; then publish. Vehicles missing year, make, model, price, or photo are excluded from the public inventory. Move sold cars to Draft. No sample vehicles are imported.
6. Add your business privacy policy in WordPress Settings → Privacy, configure delivery with your host or an SMTP service, and verify that a test inquiry is saved and its notification arrives. Then enable Online inquiries in Appearance → Iyra Motors.

Requests are saved privately in Customer inquiries and visible only to administrators. They are kept even if an email notification fails. Review this inbox regularly and delete records according to your dealership's retention policy. The `_iyra_notification_sent` post metadata records notification success. No credit applications or financial documents are collected.

The hosted ChatGPT review uses illustrative inventory and does not send or save form data. The WordPress installation reads published vehicles from your own database and starts with inquiry submission disabled until configured. Saved vehicles stay on the shopper's browser. The loan calculator is an estimate and does not contact a lender.

## Before public launch

Confirm the real inventory, images, prices, descriptions, fees, contact details, privacy policy, inquiry delivery, host backups, and access roles. This package has not been installed on your WordPress host, so the hosting-specific installation and end-to-end delivery check remain to be done. Ask your inventory provider for a feed specification to add automated inventory syncing. No DealerCenter, auction feed, financing provider, or vehicle-history service is currently connected.

## Source and updates

The shared application is `components/iyra/site.tsx`; the WordPress entry is `wordpress/entry.tsx`. Build the theme's bundled JS/CSS with the included `vite.wordpress.config.ts`, copy current image assets into `images`, and ZIP the `iyra-motors` directory. Do not edit minified bundle files.

Licensed illustrative images: see `photo-credits.json`. The supplied Iyra Motors logo is used as provided. The hero is an illustrative stock image, not a photograph of the dealership. Inventory photos in the installed theme must be your actual vehicle photos.
