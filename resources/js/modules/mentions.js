/**
 * @mention autocomplete for comment textareas (Alpine component `wbMentions`).
 *
 * Typing `@` followed by part of a name lists the people who can see the
 * task or page; choosing one inserts `@Nombre Apellido `. The server turns
 * those names into mention tokens when the comment is saved.
 */
const normalize = (text) => text.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

export function registerMentions(Alpine) {
    Alpine.data('wbMentions', (users) => ({
        users,
        open: false,
        matches: [],
        active: 0,
        start: null,
        above: false,

        textarea() {
            return this.$el.querySelector('textarea');
        },

        update(event) {
            const textarea = this.textarea();

            if (! textarea || event.target !== textarea) {
                return;
            }

            const before = textarea.value.slice(0, textarea.selectionStart);
            const match = before.match(/(?:^|[\s(])@([^\s@]*(?: [^\s@]*){0,2})$/u);

            if (! match) {
                this.close();

                return;
            }

            const query = normalize(match[1]);
            const typed = query.trimEnd();

            this.matches = this.users
                .filter((user) => normalize(user.name).includes(typed))
                .filter((user) => ! (query.endsWith(' ') && normalize(user.name) === typed))
                .slice(0, 8);

            this.start = before.length - match[1].length - 1;
            this.active = 0;
            this.above = window.innerHeight - textarea.getBoundingClientRect().bottom < 240;
            this.open = this.matches.length > 0;
        },

        keydown(event) {
            if (! this.open) {
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                this.active = (this.active + 1) % this.matches.length;
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                this.active = (this.active - 1 + this.matches.length) % this.matches.length;
            } else if (event.key === 'Enter' || event.key === 'Tab') {
                event.preventDefault();
                event.stopImmediatePropagation();
                this.choose(this.matches[this.active]);
            } else if (event.key === 'Escape') {
                event.preventDefault();
                event.stopPropagation();
                this.close();
            }
        },

        choose(user) {
            const textarea = this.textarea();

            if (! textarea || this.start === null || ! user) {
                return;
            }

            const caret = textarea.selectionStart;
            const mention = `@${user.name} `;
            const position = this.start + mention.length;

            textarea.value = textarea.value.slice(0, this.start) + mention + textarea.value.slice(caret);
            textarea.focus();
            textarea.setSelectionRange(position, position);
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
            this.close();
        },

        close() {
            this.open = false;
            this.matches = [];
            this.start = null;
        },

        initials(name) {
            return name.split(' ').filter(Boolean).slice(0, 2).map((part) => part[0]).join('');
        },
    }));
}
