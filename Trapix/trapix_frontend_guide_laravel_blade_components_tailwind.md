# Trapix Frontend Guide (Laravel + Blade Components + Tailwind)

---

# 🇬🇧 English Version

## 1. Project Overview
This project uses:
- Laravel Blade Components
- Tailwind CSS
- Reusable UI system (Cards, Sections, Shared components)

---

## 2. First Time Setup (Frontend)
Run the following commands in the **Laravel root directory**:

```bash
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p
```

Then install dependencies:

```bash
npm install
```

---

## 3. Run Project (First Time)

### Backend (Laravel)
```bash
php artisan serve
```

### Frontend (Vite)
```bash
npm run dev
```

---

## 4. Run Project (Every Time After)

```bash
php artisan serve
npm run dev
```

---

## 5. Blade Components Structure

### Shared Components
Reusable UI elements:
```blade
<x-shared.container />
<x-shared.title />
<x-shared.paragraph />
<x-shared.button />
```

---

### Cards Components
Used for displaying content blocks:
```blade
<x-cards.service />
<x-cards.info />
```

---

### Sections Components
Full page sections:
```blade
<x-sections.hero />
<x-sections.services />
<x-sections.features />
```

---

### Blocks Components
Layout helpers:
```blade
<x-blocks.group-footer-nav />
```

---

## 6. How to Use a Component

### Example: Service Card
```blade
<x-cards.service title="SEO Optimization" description="Improve your ranking">

    <x-slot:icon>
        <svg>...</svg>
    </x-slot:icon>

</x-cards.service>
```

---

### Example: Info Card
```blade
<x-cards.info title="Mission" description="Our mission is...">

    <x-slot:icon>
        <svg>...</svg>
    </x-slot:icon>

</x-cards.info>
```

---

## 7. Layout System

Always wrap content inside container:

```blade
<x-shared.container>
    ...content
</x-shared.container>
```

---

## 8. Navigation Example

```blade
<x-shared.navitem href="#" text="Home" />
```

---

## 9. Best Practices
- Use slots for icons instead of raw HTML variables
- Avoid duplicate components logic
- Keep naming consistent (service-card, info-card)
- Always use container for spacing consistency

---

# 🇪🇬 النسخة العربية

## 1. نظرة عامة على المشروع
المشروع يعتمد على:
- Laravel Blade Components
- Tailwind CSS
- نظام مكونات قابل لإعادة الاستخدام (Cards / Sections / Shared)

---

## 2. أول مرة تشغيل (Frontend)
شغل الأوامر دي داخل جذر المشروع:

```bash
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p
```

وبعدها:

```bash
npm install
```

---

## 3. تشغيل المشروع لأول مرة

### تشغيل Laravel
```bash
php artisan serve
```

### تشغيل Tailwind / Vite
```bash
npm run dev
```

---

## 4. تشغيل المشروع بعد كده

```bash
php artisan serve
npm run dev
```

---

## 5. تقسيم الكومبونانت

### Shared (مشترك)
```blade
<x-shared.container />
<x-shared.title />
<x-shared.paragraph />
<x-shared.button />
```

---

### Cards (بطاقات)
```blade
<x-cards.service />
<x-cards.info />
```

---

### Sections (أقسام الصفحة)
```blade
<x-sections.hero />
<x-sections.services />
<x-sections.features />
```

---

### Blocks (بلوكات مساعدة)
```blade
<x-blocks.group-footer-nav />
```

---

## 6. طريقة استخدام أي Component

### مثال Service Card
```blade
<x-cards.service title="SEO" description="تحسين محركات البحث">

    <x-slot:icon>
        <svg>...</svg>
    </x-slot:icon>

</x-cards.service>
```

---

### مثال Info Card
```blade
<x-cards.info title="Mission" description="هدفنا هو...">

    <x-slot:icon>
        <svg>...</svg>
    </x-slot:icon>

</x-cards.info>
```

---

## 7. نظام الـ Layout

لازم أي محتوى يكون داخل container:

```blade
<x-shared.container>
    ...
</x-shared.container>
```

---

## 8. مثال Navbar Item

```blade
<x-shared.navitem href="#" text="الرئيسية" />
```

---

## 9. أفضل الممارسات
- استخدم slots بدل تمرير HTML خام
- تجنب تكرار الكود
- حافظ على naming ثابت
- استخدم container في كل sections

---

# End of Guide 🚀

