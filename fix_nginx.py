import re

with open('/etc/nginx/sites-enabled/payment', 'r') as f:
    content = f.read()

# 1. In the SSL block (443), split the combined server_name
content = content.replace(
    'server_name payin.co.tz www.payin.co.tz login.payin.co.tz;',
    'server_name login.payin.co.tz;'
)

# 2. Add new www static site block + its HTTP redirect before the login block comment
www_block = """# ------------------------------------------
#  Website - www.payin.co.tz (static landing page)
# ------------------------------------------
server {
    server_name payin.co.tz www.payin.co.tz;

    root /var/www/payment/www;
    index index.html;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    location / {
        try_files $uri $uri/ /index.html;
    }

    listen 443 ssl;
    ssl_certificate /etc/letsencrypt/live/payin.co.tz/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/payin.co.tz/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;
}

server {
    if ($host = www.payin.co.tz) {
        return 301 https://$host$request_uri;
    }
    if ($host = payin.co.tz) {
        return 301 https://$host$request_uri;
    }
    listen 80;
    server_name payin.co.tz www.payin.co.tz;
    return 404;
}

"""

# Insert before the Payment Service comment block
marker = '# ------------------------------------------\n#  Payment Service'
if marker in content:
    content = content.replace(marker, www_block + marker, 1)
    print('Inserted www static site block')
else:
    print('ERROR: Could not find Payment Service marker')

# 3. Remove old www/payin.co.tz redirects from the login redirect block
old_redirects = """    if ($host = www.payin.co.tz) {
        return 301 https://$host$request_uri;
    } # managed by Certbot


    if ($host = payin.co.tz) {
        return 301 https://$host$request_uri;
    } # managed by Certbot


    if ($host = login.payin.co.tz) {"""

new_redirects = """    if ($host = login.payin.co.tz) {"""

content = content.replace(old_redirects, new_redirects)

with open('/etc/nginx/sites-enabled/payment', 'w') as f:
    f.write(content)

print('Config updated successfully')
