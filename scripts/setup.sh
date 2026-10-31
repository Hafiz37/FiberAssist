#!/bin/bash

echo "=== Setup FiberAssist Project ==="

# 1. Buat symbolic link ke /var/www/html
echo "1. Membuat symbolic link..."
sudo ln -sf /home/gopal-ichiro/Documents/FiberAssist /var/www/html/FiberAssist

# 2. Set permission
echo "2. Setting permission..."
sudo chmod -R 755 /home/gopal-ichiro/Documents/FiberAssist
sudo chown -R www-data:www-data /home/gopal-ichiro/Documents/FiberAssist

# 3. Restart Apache
echo "3. Restart Apache..."
sudo systemctl restart apache2

echo ""
echo "✅ Setup selesai!"
echo "Akses aplikasi di:"
echo "   Dashboard: http://localhost/FiberAssist/dashboard/"
echo "   Chat Simulator: http://localhost/FiberAssist/chat/"
