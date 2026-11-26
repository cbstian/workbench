/**
 * Emoji picker (Alpine component `wbEmojiPicker`), used by <x-ui.icon-picker>.
 *
 * Wraps emoji-picker-element (https://github.com/nolanlawson/emoji-picker-element,
 * Apache-2.0). The library and its Spanish CLDR data are self-hosted: both are
 * loaded with a dynamic import the first time a picker opens, so other pages
 * never download them. The picker's colors follow the design tokens.
 */
let pickerPromise = null;

function loadPicker() {
    pickerPromise ??= Promise.all([
        import('emoji-picker-element/picker'),
        import('emoji-picker-element/i18n/es'),
        import('emoji-picker-element-data/es/cldr/data.json?url'),
    ]).then(([pickerModule, i18nModule, dataModule]) => ({
        Picker: pickerModule.default,
        i18n: i18nModule.default,
        dataSource: dataModule.default,
    }));

    return pickerPromise;
}

export function registerEmojiPicker(Alpine) {
    Alpine.data('wbEmojiPicker', (value) => ({
        value,
        open: false,
        loading: false,

        async toggle() {
            this.open = !this.open;

            if (this.open) {
                await this.mountPicker();
            }
        },

        close() {
            this.open = false;
        },

        choose(emoji) {
            this.value = emoji;
            this.close();
            this.$refs.trigger.focus();
        },

        clear() {
            this.value = null;
            this.close();
            this.$refs.trigger.focus();
        },

        async mountPicker() {
            if (this.$refs.host.firstElementChild) {
                this.focusSearch();

                return;
            }

            this.loading = true;
            const { Picker, i18n, dataSource } = await loadPicker();
            this.loading = false;

            const picker = new Picker({ locale: 'es', dataSource, i18n });
            picker.classList.add('wb-emoji-picker');
            picker.addEventListener('emoji-click', (event) => this.choose(event.detail.unicode));
            this.$refs.host.appendChild(picker);
            this.focusSearch();
        },

        focusSearch() {
            requestAnimationFrame(() => {
                this.$refs.host.firstElementChild?.shadowRoot?.querySelector('input')?.focus();
            });
        },
    }));
}
