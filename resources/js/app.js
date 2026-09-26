import './bootstrap';

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
