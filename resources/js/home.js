import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { Flip } from 'gsap/Flip';

gsap.registerPlugin(ScrollTrigger, Flip);

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const hero = document.querySelector('[data-aura-hero]');

const clearAnimationProps = (targets) => gsap.set(targets, { clearProps: 'transform,opacity,willChange,clipPath' });

if (hero && !reducedMotion) {
    const heroCopy = hero.querySelectorAll('[data-aura-copy] > *');
    const heroVisual = hero.querySelector('[data-aura-mask]');
    const glows = hero.querySelectorAll('.aura-glow');
    const maskStart = 'inset(0 9% 0 0 round 24%)';
    const maskEnd = 'inset(0 0% 0 0 round 0%)';

    gsap.timeline({ defaults: { ease: 'power2.out' } })
        .fromTo(heroCopy, { y: 16, opacity: .92, willChange: 'transform,opacity' }, {
            y: 0,
            opacity: 1,
            duration: .68,
            stagger: .075,
            clearProps: 'transform,opacity,willChange',
        })
        .fromTo(heroVisual, { clipPath: maskStart, willChange: 'clip-path' }, {
            clipPath: maskEnd,
            duration: .9,
            ease: 'power3.inOut',
            clearProps: 'clipPath,willChange',
        }, .08);

    gsap.to(glows, {
        scale: 1.08,
        opacity: .72,
        duration: 5.5,
        stagger: 1.1,
        repeat: -1,
        yoyo: true,
        ease: 'sine.inOut',
        transformOrigin: '50% 50%',
    });

    const heroImage = heroVisual?.querySelector('img');
    if (heroImage) {
        gsap.to(heroImage, {
            yPercent: 3,
            scale: 1.025,
            ease: 'none',
            scrollTrigger: {
                trigger: hero,
                start: 'top top',
                end: 'bottom top',
                scrub: .55,
            },
        });
    }
}

if (!reducedMotion) {
    gsap.utils.toArray('[data-home-reveal]').forEach((element) => {
        gsap.fromTo(element, { y: 16, opacity: 0, willChange: 'transform,opacity' }, {
            y: 0,
            opacity: 1,
            duration: .55,
            ease: 'power2.out',
            clearProps: 'transform,opacity,willChange',
            scrollTrigger: {
                trigger: element,
                start: 'top 88%',
                once: true,
            },
        });
    });

    gsap.utils.toArray('[data-home-stagger]').forEach((group) => {
        const children = [...group.children];
        if (!children.length) return;
        gsap.fromTo(children, { y: 16, opacity: 0, willChange: 'transform,opacity' }, {
            y: 0,
            opacity: 1,
            duration: .52,
            stagger: .065,
            ease: 'power2.out',
            clearProps: 'transform,opacity,willChange',
            scrollTrigger: {
                trigger: group,
                start: 'top 87%',
                once: true,
            },
        });
    });

    gsap.utils.toArray('[data-mask-reveal]').forEach((frame) => {
        gsap.fromTo(frame, { clipPath: 'inset(0 0 100% 0 round 10px)', willChange: 'clip-path' }, {
            clipPath: 'inset(0 0 0% 0 round 10px)',
            duration: .78,
            ease: 'power3.inOut',
            clearProps: 'clipPath,willChange',
            scrollTrigger: {
                trigger: frame,
                start: 'top 91%',
                once: true,
            },
        });
    });
}

document.querySelectorAll('[data-flip-services]').forEach((grid) => {
    const getCards = () => [...grid.querySelectorAll('[data-flip-service]')];

    getCards().forEach((card) => {
        const button = card.querySelector('[data-feature-service]');
        button?.setAttribute('aria-pressed', card.classList.contains('is-featured') ? 'true' : 'false');
        button?.addEventListener('click', () => {
            if (card.classList.contains('is-featured')) return;
            const cards = getCards();
            const state = reducedMotion ? null : Flip.getState(cards);

            cards.forEach((item) => {
                item.classList.remove('is-featured');
                item.querySelector('[data-feature-service]')?.setAttribute('aria-pressed', 'false');
            });
            card.classList.add('is-featured');
            button.setAttribute('aria-pressed', 'true');
            grid.prepend(card);

            if (state) {
                Flip.from(state, {
                    duration: .68,
                    ease: 'power2.inOut',
                    absolute: true,
                    scale: true,
                    nested: true,
                    onEnter: (elements) => gsap.fromTo(elements, { opacity: .86 }, { opacity: 1, duration: .32 }),
                    onComplete: () => clearAnimationProps(cards),
                });
            }
        });
    });
});

window.addEventListener('load', () => ScrollTrigger.refresh(), { once: true });
