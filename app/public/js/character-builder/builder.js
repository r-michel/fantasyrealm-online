import { CharacterRenderer } from './renderer.js';

class CharacterBuilder {
    /**
     * @param {HTMLFormElement} form
     */
    constructor(form) {
        this.form = form;

        this.canvas = document.querySelector(
            '#character-preview-canvas',
        );

        this.generatedImageInput = this.form.querySelector(
            '[name$="[generatedImage]"]',
        );

        this.previewName = document.querySelector('#preview-name');
        this.previewGender = document.querySelector('#preview-gender');
        this.previewEquipmentCount = document.querySelector(
            '#preview-equipment-count',
        );

        this.configUrl =
            '/assets/character-builder/builder-config.json';

        this.config = null;
        this.renderer = null;
        this.renderVersion = 0;
    }

    async init() {
        if (!this.canvas) {
            throw new Error(
                'Le canvas #character-preview-canvas est introuvable.',
            );
        }

        if (!this.generatedImageInput) {
            throw new Error(
                'Le champ generatedImage est introuvable.',
            );
        }

        this.config = await this.loadConfig();

        this.renderer = await new CharacterRenderer(
            this.canvas,
        ).load();

        this.renderer.builderConfig = this.config;

        this.bindFormEvents();
        this.bindEquipmentSelection();
        this.bindSubmission();

        await this.updatePreview();
    }

    async loadConfig() {
        const response = await fetch(this.configUrl, {
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error(
                `Impossible de charger builder-config.json : HTTP ${response.status}`,
            );
        }

        return response.json();
    }

    /**
     * Get symfony property
     *
     * @param {string} property
     * @returns {NodeListOf<HTMLInputElement>}
     */
    getInputs(property) {
        return this.form.querySelectorAll(
            `[name$="[${property}]"], [name$="[${property}][]"]`,
        );
    }

    /**
     * @param {string} property
     * @param {string|null} fallback
     */
    getSelectedValue(property, fallback = null) {
        const inputs = [...this.getInputs(property)];

        if (inputs.length === 0) {
            return fallback;
        }

        const radioOrCheckbox = inputs.find(
            (input) =>
                ['radio', 'checkbox'].includes(input.type) &&
                input.checked,
        );

        if (radioOrCheckbox) {
            return radioOrCheckbox.value;
        }

        return inputs[0]?.value || fallback;
    }

    /**
     * @returns {HTMLInputElement[]}
     */
    getSelectedEquipmentInputs() {
        return [...this.getInputs('equipment')].filter(
            (input) => input.checked,
        );
    }

    getState() {
        return {
            name: this.getSelectedValue('name', 'Personnage'),
            gender: this.getSelectedValue(
                'gender',
                this.config.defaults.gender,
            ),
            skinColor: this.getSelectedValue(
                'skinColor',
                this.config.defaults.skinColor,
            ),
            hairColor: this.getSelectedValue(
                'hairColor',
                this.config.defaults.hairColor,
            ),
            eyeColor: this.getSelectedValue(
                'eyeColor',
                this.config.defaults.eyeColor,
            ),
            eyeShape: this.getSelectedValue(
                'eyeShape',
                this.config.defaults.eyeShape,
            ),
            noseShape: this.getSelectedValue(
                'noseShape',
                this.config.defaults.noseShape,
            ),
            mouthShape: this.getSelectedValue(
                'mouthShape',
                this.config.defaults.mouthShape,
            ),
            equipment: this.getSelectedEquipmentInputs().map((input) => ({
                id: input.value,
                imageUrl: input.dataset.imageUrl || null,
                category: input.dataset.categoryCode || null,
            })),
        };
    }

    bindFormEvents() {
        const watchedProperties = [
            'name',
            'gender',
            'skinColor',
            'hairColor',
            'eyeColor',
            'eyeShape',
            'noseShape',
            'mouthShape',
        ];

        watchedProperties.forEach((property) => {
            this.getInputs(property).forEach((input) => {
                const eventName =
                    input.type === 'text' ? 'input' : 'change';

                input.addEventListener(eventName, () => {
                    this.updatePreview().catch((error) => {
                        console.error(
                            'Erreur pendant la prévisualisation.',
                            error,
                        );
                    });
                });
            });
        });
    }

    bindSubmission() {
        this.form.addEventListener('submit', async (event) => {
            if (this.form.dataset.imageReady === 'true') {
                return;
            }

            event.preventDefault();

            try {
                await this.updatePreview();

                this.generatedImageInput.value =
                    this.renderer.toDataUrl('image/png');

                this.form.dataset.imageReady = 'true';

                this.form.requestSubmit(
                    event.submitter ?? undefined,
                );
            } catch (error) {
                console.error(
                    'Impossible de générer l’image finale.',
                    error,
                );

                window.alert(
                    'Une erreur empêche la génération de l’image du personnage.',
                );
            }
        });
    }

    bindEquipmentSelection() {
        const equipmentInputs = this.getInputs('equipment');

        equipmentInputs.forEach((input) => {
            input.addEventListener('change', () => {
                if (!input.checked) {
                    return;
                }

                const selectedCategory =
                    input.dataset.categoryCode;

                if (!selectedCategory) {
                    return;
                }

                equipmentInputs.forEach((otherInput) => {
                    if (
                        otherInput !== input
                        && otherInput.dataset.categoryCode === selectedCategory
                    ) {
                        otherInput.checked = false;
                    }
                });

                this.updatePreview().catch((error) => {
                    console.error(
                        'Erreur pendant la mise à jour des équipements.',
                        error,
                    );
                });
            });
        });
    }

    /**
     * Find a layer in config
     *
     * @param {string} section
     * @param {string} value
     */
    findLayer(section, value) {
        return this.config.layers?.[section]?.[value] ?? null;
    }

    /**
     * Build layers list to draw
     *
     * @param {object} state
     * @returns {Array<object>}
     */
    buildLayers(state) {
        return {
            body: [
                this.findLayer(
                    'body',
                    `${state.gender}.${state.skinColor}`,
                ),
            ].filter(Boolean),

            hair: [
                this.findLayer(
                    'hair',
                    `${state.gender}.${state.hairColor}`,
                ),
            ].filter(Boolean),
        };
    }

    async updatePreview() {
        const state = this.getState();
        const layers = this.buildLayers(state);

        const accessory = state.equipment.filter(
            (equipment) => equipment.category === 'accessory',
        );

        const otherEquipment = state.equipment.filter(
            (equipment) => equipment.category !== 'accessory',
        );

        this.renderer.render(layers.body);

        this.renderer.drawEyes({
            shape: state.eyeShape,
            color: state.eyeColor,
        });

        this.renderer.drawNose({
            shape: state.noseShape,
        });

        this.renderer.drawMouth({
            shape: state.mouthShape,
        });

        await this.drawEquipmentLayers(otherEquipment);
        this.renderer.render(layers.hair, false);
        await this.drawEquipmentLayers(accessory);

        this.updateTextPreview(state);

        this.generatedImageInput.value =
            this.renderer.toDataUrl('image/png');
    }

    updateTextPreview(state) {
        if (this.previewName) {
            this.previewName.textContent =
                state.name?.trim() || 'Personnage';
        }

        if (this.previewGender) {
            const label =
                this.config.labels?.gender?.[state.gender] ??
                state.gender;

            this.previewGender.textContent = label;
        }

        if (this.previewEquipmentCount) {
            this.previewEquipmentCount.textContent =
                `${state.equipment.length} équip.`;
        }
    }

    async drawEquipmentLayers(equipmentItems) {
        const categoriesConfig =
            this.config?.equipmentCategories ?? {};

        const configuredItems = equipmentItems
            .map((equipment) => {
                const categoryConfig =
                    categoriesConfig[equipment.category] ?? {};

                return {
                    ...equipment,
                    scale: Number(categoryConfig.scale ?? 1),
                    offsetX: Number(categoryConfig.offsetX ?? 0),
                    offsetY: Number(categoryConfig.offsetY ?? 0),
                    zIndex: Number(categoryConfig.zIndex ?? 100),
                };
            })
            .sort((a, b) => a.zIndex - b.zIndex);

        for (const equipment of configuredItems) {
            if (!equipment.imageUrl) {
                continue;
            }

            const image = await this.loadExternalImage(
                equipment.imageUrl,
            );

            const width = image.naturalWidth * equipment.scale;
            const height = image.naturalHeight * equipment.scale;

            const x =
                (this.renderer.canvas.width - width) / 2
                + equipment.offsetX;

            const y =
                (this.renderer.canvas.height - height) / 2
                + equipment.offsetY;

            this.renderer.context.save();
            this.renderer.context.imageSmoothingEnabled = false;

            this.renderer.context.drawImage(
                image,
                Math.round(x),
                Math.round(y),
                Math.round(width),
                Math.round(height),
            );

            this.renderer.context.restore();
        }
    }

    loadExternalImage(source) {
        return new Promise((resolve, reject) => {
            const image = new Image();

            image.onload = () => resolve(image);
            image.onerror = () => reject(
                new Error(
                    `Impossible de charger l’équipement : ${source}`,
                ),
            );

            image.src = source;
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(
        '#character-builder-form',
    );

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const builder = new CharacterBuilder(form);

    builder.init().catch((error) => {
        console.error(
            'Initialisation du Character Builder impossible.',
            error,
        );
    });
});
