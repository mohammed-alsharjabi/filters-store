# نشر موقع فلاتر وتحلية المياه على Hostinger

لا يوجد نطاق إنتاج معتمد داخل المستودع. قبل النشر احصل من hPanel على النطاق الصحيح، ومسار `public_html` المطلق، وبيانات MySQL. يجب أن يكون Document Root للموقع هو مجلد Laravel `public` فقط.

## الإعداد الأول

استبدل `USER` و`DOMAIN` بالقيم المؤكدة من Hostinger:

```bash
git clone https://github.com/mohammed-alsharjabi/filters-store.git /home/USER/filters-store-source
mkdir -p /home/USER/filters-store-app/shared
cp /home/USER/filters-store-source/.env.production.example /home/USER/filters-store-app/shared/.env
chmod 600 /home/USER/filters-store-app/shared/.env
```

عدّل الملف المشترك وأدخل `APP_URL` و`ASSET_URL` وبيانات MySQL وبيانات المدير. أنشئ `APP_KEY` مرة واحدة ولا ترفعه إلى GitHub:

```bash
cd /home/USER/filters-store-source
php artisan key:generate --show
```

## النشر الآمن

سكربت النشر يرفض العمل قبل إعطائه المسار المتوقع صراحة في `EXPECTED_PUBLIC_TARGET`. هذا يمنع النشر بالخطأ إلى نطاق أو مجلد آخر.

```bash
cd /home/USER/filters-store-source
git pull --ff-only origin main
export EXPECTED_PUBLIC_TARGET=/home/USER/domains/DOMAIN/public_html
bash deploy/hostinger-release.sh \
  /home/USER/filters-store-source \
  /home/USER/filters-store-app \
  /home/USER/domains/DOMAIN/public_html
```

إذا لم يكن Node.js متاحًا على الخادم، ابنِ الأصول محليًا وارفع محتوى `public/build` إلى `/home/USER/filters-store-app/shared/build` قبل تشغيل السكربت.

عند أول نشر فقط، أنشئ بيانات الخدمات والمقالات وحساب المدير:

```bash
php /home/USER/filters-store-app/current/artisan db:seed --force
```

## التحقق

استبدل `DOMAIN` بالنطاق الفعلي:

```bash
curl -I https://DOMAIN/
curl -I https://DOMAIN/admin/login
curl https://DOMAIN/robots.txt
curl https://DOMAIN/sitemap_index.xml
```

ينقل السكربت مجلد `public_html` السابق إلى نسخة احتياطية مؤرخة قبل ربط الإصدار الجديد. لا تحذفها إلا بعد فحص الصفحات، والنماذج، والصور، وقاعدة البيانات.
