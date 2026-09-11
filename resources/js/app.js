document.documentElement.classList.add('reveal-enabled');

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
document.querySelector('[data-admin-menu]')?.addEventListener('click', () => document.querySelector('.admin-sidebar')?.classList.toggle('open'));
const toggle = document.querySelector('[data-nav-toggle]');
const drawer = document.querySelector('[data-mobile-drawer]');
const backdrop = document.querySelector('[data-nav-backdrop]');

const setNavigation = (open) => {
    if (!drawer || !backdrop || !toggle) return;
    drawer.classList.toggle('open', open);
    drawer.setAttribute('aria-hidden', open ? 'false' : 'true');
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    backdrop.hidden = !open;
    document.body.classList.toggle('navigation-open', open);
};

toggle?.addEventListener('click', () => setNavigation(!drawer?.classList.contains('open')));
backdrop?.addEventListener('click', () => setNavigation(false));
document.querySelector('[data-nav-close]')?.addEventListener('click', () => setNavigation(false));
drawer?.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setNavigation(false)));

document.querySelectorAll('[data-image-shell]').forEach((shell) => {
    const image = shell.querySelector('img');
    if (!image) return;
    const done = () => shell.classList.remove('is-loading');
    if (image.complete && image.naturalWidth) done();
    image.addEventListener('load', done, { once: true });
    image.addEventListener('error', () => {
        done();
        shell.classList.add('has-error');
    }, { once: true });
});

if (!reducedMotion && 'IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-revealed');
            observer.unobserve(entry.target);
        });
    }, { rootMargin: '0px 0px -6% 0px', threshold: 0.08 });
    document.querySelectorAll('[data-reveal]').forEach((element) => observer.observe(element));
} else {
    document.querySelectorAll('[data-reveal]').forEach((element) => element.classList.add('is-revealed'));
}

const lightbox = document.querySelector('[data-lightbox]');
const lightboxItems = [...document.querySelectorAll('[data-lightbox-item]')];
let lightboxIndex = 0;

const showLightboxImage = (index) => {
    if (!lightbox || !lightboxItems.length) return;
    lightboxIndex = (index + lightboxItems.length) % lightboxItems.length;
    const item = lightboxItems[lightboxIndex];
    const image = lightbox.querySelector('img');
    const caption = lightbox.querySelector('figcaption');
    image.src = item.dataset.lightboxSrc;
    image.alt = item.dataset.lightboxAlt || '';
    caption.textContent = item.dataset.lightboxCaption || item.dataset.lightboxAlt || '';
};

const setLightbox = (open, index = lightboxIndex) => {
    if (!lightbox) return;
    if (open) showLightboxImage(index);
    lightbox.hidden = !open;
    document.body.classList.toggle('lightbox-open', open);
    if (open) lightbox.querySelector('[data-lightbox-close]')?.focus();
};

lightboxItems.forEach((item, index) => item.addEventListener('click', () => setLightbox(true, index)));
lightbox?.querySelector('[data-lightbox-close]')?.addEventListener('click', () => setLightbox(false));
lightbox?.querySelector('[data-lightbox-prev]')?.addEventListener('click', () => showLightboxImage(lightboxIndex - 1));
lightbox?.querySelector('[data-lightbox-next]')?.addEventListener('click', () => showLightboxImage(lightboxIndex + 1));
lightbox?.addEventListener('click', (event) => { if (event.target === lightbox) setLightbox(false); });

document.querySelectorAll('[data-gallery-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const gallery = button.closest('.srvc-shell')?.querySelector('[data-service-gallery]');
        const extras = [...(gallery?.querySelectorAll('[data-gallery-extra]') || [])];
        const expanding = extras.some((item) => item.hidden);
        extras.forEach((item) => {
            item.hidden = !expanding;
            item.classList.toggle('is-revealed', expanding);
        });
        button.innerHTML = `${expanding ? button.dataset.expandedLabel : button.dataset.collapsedLabel} <span aria-hidden="true">${expanding ? '→' : '←'}</span>`;
        button.setAttribute('aria-expanded', expanding ? 'true' : 'false');
    });
});

document.querySelectorAll('[data-related-carousel]').forEach((carousel) => {
    const track = carousel.querySelector('[data-related-track]');
    const move = (direction) => track?.scrollBy({ left: direction * track.clientWidth * .72, behavior: reducedMotion ? 'auto' : 'smooth' });
    carousel.querySelector('[data-related-prev]')?.addEventListener('click', () => move(1));
    carousel.querySelector('[data-related-next]')?.addEventListener('click', () => move(-1));
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        setNavigation(false);
        setLightbox(false);
    }
    if (!lightbox?.hidden && event.key === 'ArrowLeft') showLightboxImage(lightboxIndex + 1);
    if (!lightbox?.hidden && event.key === 'ArrowRight') showLightboxImage(lightboxIndex - 1);
});

document.querySelectorAll('.lead-form').forEach((form) => {
    form.addEventListener('submit', () => {
        const button = form.querySelector('button[type="submit"]');
        if (!button || button.disabled) return;
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.textContent = form.querySelector('[name="submission_channel"]')?.value === 'whatsapp'
            ? 'جارٍ تجهيز رسالة واتساب…'
            : 'جارٍ إرسال الطلب…';
    });

    const imageInput = form.querySelector('[data-image-input]');
    const uploadAction = imageInput?.closest('.upload-field')?.querySelector('.upload-action');
    imageInput?.addEventListener('change', () => {
        if (!uploadAction) return;
        const count = imageInput.files?.length || 0;
        uploadAction.textContent = count ? `تم اختيار ${count} ${count === 1 ? 'صورة' : 'صور'}` : 'اختيار الصور';
    });
});

document.querySelectorAll('[data-before-after]').forEach((comparison) => {
    const range = comparison.querySelector('[data-before-after-range]');
    range?.addEventListener('input', () => comparison.style.setProperty('--position', `${range.value}%`));
});
