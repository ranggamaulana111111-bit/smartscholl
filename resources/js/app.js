import './bootstrap';
import './attendance-scan';

document.addEventListener('DOMContentLoaded', () => {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const revealEls = document.querySelectorAll('.reveal');
    if (revealEls.length) {
        if ('IntersectionObserver' in window && !reduceMotion) {
            const io = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            io.unobserve(entry.target);
                        }
                    });
                },
                { threshold: 0.1, rootMargin: '0px 0px -40px 0px' }
            );
            revealEls.forEach((el) => io.observe(el));
        } else {
            revealEls.forEach((el) => el.classList.add('is-visible'));
        }
    }

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const message = form.getAttribute('data-confirm');
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('form[data-busy]').forEach((form) => {
        form.addEventListener('submit', () => {
            form.querySelectorAll('button[type="submit"]').forEach((btn) => {
                btn.disabled = true;
                btn.classList.add('btn-loading');
                btn.setAttribute('aria-busy', 'true');
            });
        });
    });

    // Tombol tampilkan/sembunyikan kata sandi. Fokus sengaja ditahan di
    // tombol supaya pengguna bisa menyalakan dan mematikan berulang kali
    // tanpa kehilangan posisi kursor di kolom kata sandi.
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = document.getElementById(button.dataset.passwordToggle);
        if (!input) {
            return;
        }

        const showIcon = button.querySelector('[data-password-icon="show"]');
        const hideIcon = button.querySelector('[data-password-icon="hide"]');

        const applyState = (revealed) => {
            input.type = revealed ? 'text' : 'password';
            button.setAttribute('aria-label', revealed ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
            if (showIcon) showIcon.classList.toggle('hidden', revealed);
            if (hideIcon) hideIcon.classList.toggle('hidden', !revealed);
        };

        applyState(input.type === 'text');

        button.addEventListener('click', () => {
            applyState(input.type !== 'text');
        });
    });

    // Menu bagian halaman di layar kecil memakai <details>. Menutupnya setelah
    // tautan diklik supaya panel tidak tetap menutupi isi halaman.
    document.querySelectorAll('details.disclosure a[href^="#"]').forEach((link) => {
        link.addEventListener('click', () => {
            const details = link.closest('details');
            if (details) {
                details.removeAttribute('open');
            }
        });
    });

    // Escape menutup menu yang sama dan mengembalikan fokus ke pemicunya,
    // karena <details> bawaan peramban tidak melakukannya.
    document.querySelectorAll('details.disclosure').forEach((details) => {
        details.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape' || !details.open) {
                return;
            }

            event.preventDefault();
            details.removeAttribute('open');
            const summary = details.querySelector('summary');
            if (summary) {
                summary.focus();
            }
        });
    });

    // Penanda bagian aktif di navigasi: garis putus-putus di bawah tautan yang
    // bagiannya sedang melewati garis baca (30% tinggi layar).
    document.querySelectorAll('[data-nav-spy]').forEach((nav) => {
        const links = Array.from(nav.querySelectorAll('[data-spy-target]'))
            .map((link) => ({ link, section: document.querySelector(link.dataset.spyTarget) }))
            .filter((item) => item.section);

        if (!links.length) {
            return;
        }

        const sync = () => {
            const marker = window.innerHeight * 0.3;
            let active = null;

            links.forEach((item) => {
                if (item.section.getBoundingClientRect().top <= marker) {
                    active = item;
                }
            });

            links.forEach((item) => {
                if (item === active) {
                    item.link.setAttribute('aria-current', 'true');
                } else {
                    item.link.removeAttribute('aria-current');
                }
            });
        };

        sync();
        window.addEventListener('scroll', sync, { passive: true });
        window.addEventListener('resize', sync);
    });

    document.querySelectorAll('[data-schedule-teacher-select]').forEach((teacherSelect) => {
        const subjectSelect = teacherSelect.form?.querySelector('[data-schedule-subject-select]');

        if (!subjectSelect) {
            return;
        }

        const subjectOptions = Array.from(subjectSelect.querySelectorAll('option[data-teacher-ids]'));

        const syncSubjects = () => {
            const teacherId = teacherSelect.value;
            const availableOptions = subjectOptions.filter((option) =>
                (option.dataset.teacherIds ?? '').split(' ').includes(teacherId)
            );

            subjectOptions.forEach((option) => {
                const available = availableOptions.includes(option);
                option.hidden = !available;
                option.disabled = !available;
            });

            const selectedOption = availableOptions.find((option) => option.value === subjectSelect.value);
            subjectSelect.value = selectedOption?.value ?? availableOptions[0]?.value ?? '';
            subjectSelect.disabled = !teacherId || availableOptions.length === 0;
        };

        teacherSelect.addEventListener('change', syncSubjects);
        syncSubjects();
    });
});
