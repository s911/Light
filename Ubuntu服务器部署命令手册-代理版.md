# Ubuntu 服务器部署命令手册（代理版）

适用系统：`Ubuntu 22.04 LTS`  
站点：WordPress + WooCommerce（舞台灯独立站）  
说明：本文含代理配置，满足内网安装要求。

---

## 1. 基础变量（先替换）

```bash
export DOMAIN="example.com"
export WWW_DOMAIN="www.example.com"
export APP_DIR="/var/www/stage-lighting"
export DB_NAME="stage_lighting"
export DB_USER="stage_user"
export DB_PASS="change_me_user"
export DB_ROOT_PASS="change_me_root"
export PROXY_URL="http://10.144.1.10:8080"
```

---

## 2. 配置系统代理

```bash
echo 'Acquire::http::Proxy "http://10.144.1.10:8080/";' | sudo tee /etc/apt/apt.conf.d/99proxy
echo 'Acquire::https::Proxy "http://10.144.1.10:8080/";' | sudo tee -a /etc/apt/apt.conf.d/99proxy
```

临时环境变量（当前 shell）：

```bash
export http_proxy=$PROXY_URL
export https_proxy=$PROXY_URL
```

---

## 3. 安装运行环境

```bash
sudo apt update
sudo apt install -y nginx mysql-server unzip curl software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2-fpm php8.2-mysql php8.2-curl php8.2-xml php8.2-gd php8.2-mbstring php8.2-zip php8.2-intl php8.2-bcmath
```

---

## 4. 初始化数据库

```bash
sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '${DB_ROOT_PASS}'; FLUSH PRIVILEGES;"
sudo mysql -uroot -p${DB_ROOT_PASS} -e "CREATE DATABASE ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -uroot -p${DB_ROOT_PASS} -e "CREATE USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
sudo mysql -uroot -p${DB_ROOT_PASS} -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost'; FLUSH PRIVILEGES;"
```

---

## 5. 下载并部署 WordPress（带代理）

```bash
sudo mkdir -p ${APP_DIR}
cd /tmp
curl -x ${PROXY_URL} -O https://wordpress.org/latest.zip
unzip -o latest.zip
sudo rsync -avP wordpress/ ${APP_DIR}/
```

---

## 6. 配置 WordPress

```bash
cd ${APP_DIR}
sudo cp wp-config-sample.php wp-config.php
sudo sed -i "s/database_name_here/${DB_NAME}/" wp-config.php
sudo sed -i "s/username_here/${DB_USER}/" wp-config.php
sudo sed -i "s/password_here/${DB_PASS}/" wp-config.php
sudo chown -R www-data:www-data ${APP_DIR}
sudo find ${APP_DIR} -type d -exec chmod 755 {} \;
sudo find ${APP_DIR} -type f -exec chmod 644 {} \;
```

添加缓存/安全建议配置（追加到 `wp-config.php`）：

```php
define('DISALLOW_FILE_EDIT', true);
define('WP_POST_REVISIONS', 10);
define('AUTOSAVE_INTERVAL', 300);
```

---

## 7. Nginx 站点配置

```bash
sudo tee /etc/nginx/sites-available/stage-lighting.conf >/dev/null <<EOF
server {
    listen 80;
    server_name ${DOMAIN} ${WWW_DOMAIN};
    root ${APP_DIR};
    index index.php index.html;

    location / {
        try_files \$uri \$uri/ /index.php?\$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }

    location ~* \.(jpg|jpeg|png|gif|css|js|ico|svg|woff2?)$ {
        expires 30d;
        access_log off;
    }
}
EOF
sudo ln -sf /etc/nginx/sites-available/stage-lighting.conf /etc/nginx/sites-enabled/stage-lighting.conf
sudo nginx -t
sudo systemctl reload nginx
```

---

## 8. SSL 证书（Let’s Encrypt）

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d ${DOMAIN} -d ${WWW_DOMAIN} --agree-tos -m admin@${DOMAIN} --no-eff-email -n
sudo systemctl status certbot.timer
```

---

## 9. 安装 WP-CLI（可选，带代理）

```bash
cd /tmp
curl -x ${PROXY_URL} -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
php wp-cli.phar --info
chmod +x wp-cli.phar
sudo mv wp-cli.phar /usr/local/bin/wp
```

初始化站点（示例）：

```bash
cd ${APP_DIR}
sudo -u www-data wp core install \
  --url="https://${DOMAIN}" \
  --title="Stage Lighting" \
  --admin_user="admin" \
  --admin_password="change_me_admin" \
  --admin_email="admin@${DOMAIN}"
```

---

## 10. 安装插件（后台或 WP-CLI）

建议插件：

- WooCommerce
- Stage Lighting B2B Quote
- Stage Lighting Site Setup
- Rank Math
- Wordfence
- LiteSpeed Cache / WP Rocket
- UpdraftPlus

说明：

- WordPress 后台安装插件不支持 `--proxy` 参数。
- 若必须命令行下载插件包，使用 `curl -x ${PROXY_URL}` 先下载 zip，再后台上传或 WP-CLI 安装。

---

## 11. 防火墙与服务自启

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw --force enable
sudo systemctl enable nginx php8.2-fpm mysql
sudo systemctl restart nginx php8.2-fpm mysql
```

---

## 12. 备份脚本示例

```bash
sudo mkdir -p /opt/backups/stage-lighting
sudo tee /usr/local/bin/backup-stage-lighting.sh >/dev/null <<'EOF'
#!/usr/bin/env bash
set -e
DATE=$(date +%F-%H%M)
APP_DIR="/var/www/stage-lighting"
DB_NAME="stage_lighting"
DB_USER="stage_user"
DB_PASS="change_me_user"
TARGET="/opt/backups/stage-lighting"

mysqldump -u${DB_USER} -p${DB_PASS} ${DB_NAME} > ${TARGET}/db-${DATE}.sql
tar -czf ${TARGET}/wp-content-${DATE}.tar.gz ${APP_DIR}/wp-content
find ${TARGET} -type f -mtime +14 -delete
EOF
sudo chmod +x /usr/local/bin/backup-stage-lighting.sh
```

加入定时任务（每天凌晨 3 点）：

```bash
(sudo crontab -l 2>/dev/null; echo "0 3 * * * /usr/local/bin/backup-stage-lighting.sh") | sudo crontab -
```

---

## 13. 故障排查命令

```bash
sudo tail -n 100 /var/log/nginx/error.log
sudo journalctl -u php8.2-fpm -n 100 --no-pager
sudo journalctl -u mysql -n 100 --no-pager
df -h
free -m
```

---

## 14. 代理要求速记

所有命令行安装或下载动作，统一使用代理：

- `--proxy=http://10.144.1.10:8080`
- 或 `curl -x http://10.144.1.10:8080 ...`

