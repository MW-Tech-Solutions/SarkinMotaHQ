<?php
/**
 * XML Sitemap Generator - Sarkin Mota HQ
 */
header("Content-Type: application/xml; charset=utf-8");

$base_url = "https://www.sarkinmotahq.com/";

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Core Pages -->
    <url>
        <loc><?php echo $base_url; ?>index.php</loc>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?php echo $base_url; ?>about.php</loc>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?php echo $base_url; ?>services.php</loc>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?php echo $base_url; ?>projects.php</loc>
        <priority>0.7</priority>
    </url>
    <url>
        <loc><?php echo $base_url; ?>properties.php</loc>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?php echo $base_url; ?>news.php</loc>
        <priority>0.7</priority>
    </url>
    <url>
        <loc><?php echo $base_url; ?>careers.php</loc>
        <priority>0.6</priority>
    </url>
    <url>
        <loc><?php echo $base_url; ?>contact.php</loc>
        <priority>0.8</priority>
    </url>
    
    <!-- Divisions -->
    <url>
        <loc><?php echo $base_url; ?>divisions/agriculture.php</loc>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?php echo $base_url; ?>divisions/estate-management.php</loc>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?php echo $base_url; ?>divisions/environmental.php</loc>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?php echo $base_url; ?>divisions/development.php</loc>
        <priority>0.8</priority>
    </url>
</urlset>
