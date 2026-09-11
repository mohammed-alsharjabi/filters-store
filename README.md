# فلاتر وتحلية المياه بالرياض

موقع Laravel عربي وRTL لخدمات تركيب وصيانة فلاتر المياه وأجهزة ومحطات التحلية داخل الرياض. يعتمد على Blade للصفحات العامة وLivewire للوحة الإدارة، مع تواصل مباشر عبر الاتصال وواتساب.

## النطاق الحالي

- عشر خدمات منشورة لتركيب وصيانة الفلاتر والتحلية وفلتر الجامبو والشاور وأنظمة الضباب والرذاذ.
- عشر مقالات عربية مرتبطة بالخدمات ومحسّنة للبحث المحلي.
- بيانات التواصل: الرياض، حي المونسية، والرقم `+966509439667`.
- لا توجد أسعار أو باقات أو منتجات منشورة في هذه المرحلة. تُضاف بعد اعتمادها من صاحب النشاط.
- الصور العامة المستخدمة هي الصور العشر والشعار المرفقة مع المشروع فقط.

## التشغيل المحلي

يتطلب المشروع PHP 8.3 أو أحدث، Composer 2، وNode.js 20 أو أحدث.

```bash
composer install
cp .env.example .env
touch database/database.sqlite
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm ci
npm run build
composer run dev
```

ملف البيئة المحلي مضبوط افتراضيًا للعمل مع SQLite. يمكن استخدام MySQL بتعديل متغيرات قاعدة البيانات.

## حساب المدير

لا توجد كلمة مرور افتراضية. أضف القيم التالية إلى `.env` قبل تشغيل `php artisan db:seed`:

```dotenv
ADMIN_NAME="مدير الموقع"
ADMIN_EMAIL="admin@example.com"
ADMIN_PASSWORD="كلمة مرور طويلة وفريدة"
```

صفحة الدخول هي `/admin/login`. من لوحة الإدارة يمكن تعديل بيانات النشاط والخدمات والمقالات وبيانات SEO وطلبات العملاء.

## الصور

الصور المعتمدة محفوظة في `storage/app/public/services`، والشعار في `public/brand`. رابط `public/storage` مطلوب لعرض صور الخدمات والمقالات:

```bash
php artisan storage:link
```

يدعم المشروع أيضًا استيراد صور جديدة إلى معرض الخدمات من ملف مضغوط عند الحاجة:

```bash
php artisan services --zip=/absolute/path/assets.zip
```

## الإنتاج

انسخ `.env.production.example` إلى ملف بيئة خاص بالخادم، ثم اضبط النطاق وقاعدة البيانات وحساب المدير. يجب أن يشير Document Root إلى مجلد `public` فقط.

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
npm ci
npm run build
php artisan optimize
```

يوجد دليل نشر عام على Hostinger في [docs/DEPLOYMENT_HOSTINGER.md](docs/DEPLOYMENT_HOSTINGER.md). لا يحتوي المشروع على نطاق إنتاج مفترض؛ يجب إدخال النطاق والمسار المؤكدين قبل النشر.

## الفحص

```bash
php artisan test
vendor/bin/pint --test
npm run build
composer audit
npm audit
```
