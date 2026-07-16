const DEFAULT_TILE_OPTIONS = {
    x: null,
    y: null,
    scale: 14,
    offsetX: 0,
    offsetY: 0,
    anchorX: 'center',
    anchorY: 'center',
    baselineY: null,
    flipX: false,
    opacity: 1,
};

export class CharacterRenderer {

    /**
     * @param {HTMLCanvasElement} canvas
     * @param {string} atlasUrl
     */
    constructor(
        canvas,
        atlasUrl = '/assets/character-builder/atlas.json',
    ) {
        if (!(canvas instanceof HTMLCanvasElement)) {
            throw new TypeError('Le canvas de prévisualisation est introuvable.');
        }

        this.canvas = canvas;
        this.context = canvas.getContext('2d');

        if (!this.context) {
            throw new Error('Le contexte Canvas 2D est indisponible.');
        }

        this.atlasUrl = atlasUrl;
        this.atlas = null;
        this.spritesheet = null;
        this.tiles = new Map();
    }

    /**
     * Charge l’atlas JSON et le spritesheet.
     *
     * @returns {Promise<CharacterRenderer>}
     */
    async load() {
        const response = await fetch(this.atlasUrl, {
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error(
                `Impossible de charger l’atlas : HTTP ${response.status}`,
            );
        }

        this.atlas = await response.json();

        if (!Array.isArray(this.atlas.tiles)) {
            throw new Error('Le fichier atlas.json est invalide.');
        }

        this.tiles = new Map(
            this.atlas.tiles.map((tile) => [tile.id, tile]),
        );

        this.spritesheet = await this.loadImage(this.atlas.image);

        return this;
    }

    /**
     * @param {string} source
     * @returns {Promise<HTMLImageElement>}
     */
    loadImage(source) {
        return new Promise((resolve, reject) => {
            const image = new Image();

            image.onload = () => resolve(image);
            image.onerror = () => {
                reject(
                    new Error(`Impossible de charger l’image : ${source}`),
                );
            };

            image.src = source;
        });
    }

    clear() {
        this.context.clearRect(
            0,
            0,
            this.canvas.width,
            this.canvas.height,
        );
    }

    /**
     * Dessine une tuile de l’atlas.
     *
     * @param {string} tileId
     * @param {{
     *     x?: number,
     *     y?: number,
     *     scale?: number,
     *     flipX?: boolean,
     *     opacity?: number
     * }} options
     */
    drawTile(tileId, options = {}) {
        if (!this.spritesheet) {
            throw new Error('Le renderer doit être chargé avant le rendu.');
        }

        const tile = this.tiles.get(tileId);

        if (!tile) {
            console.warn(`Tuile inconnue ignorée : ${tileId}`);
            return;
        }

        const {
            x,
            y,
            scale,
            offsetX,
            offsetY,
            anchorX,
            anchorY,
            baselineY,
            flipX,
            opacity,
        } = {
            ...DEFAULT_TILE_OPTIONS,
            ...options,
        };

        const destinationWidth = tile.width * scale;
        const destinationHeight = tile.height * scale;

        let destinationX = x;
        let destinationY = y;

        if (destinationX === null) {
            switch (anchorX) {
                case 'right':
                    destinationX = this.canvas.width - destinationWidth;
                    break;

                case 'left':
                    destinationX = 0;
                    break;

                case 'center':
                default:
                    destinationX =
                        (this.canvas.width - destinationWidth) / 2;
            }
        }

        if (destinationY === null) {
            switch (anchorY) {
                case 'bottom': {
                    const bottomLine =
                        baselineY ?? this.canvas.height - 24;

                    destinationY =
                        bottomLine - destinationHeight;
                    break;
                }

                case 'top':
                    destinationY = 0;
                    break;

                case 'center':
                default:
                    destinationY =
                        (this.canvas.height - destinationHeight) / 2;
            }
        }

        destinationX += offsetX;
        destinationY += offsetY;

        this.context.save();
        this.context.imageSmoothingEnabled = false;
        this.context.globalAlpha = opacity;

        if (flipX) {
            this.context.translate(
                destinationX + destinationWidth,
                destinationY,
            );
            this.context.scale(-1, 1);

            this.context.drawImage(
                this.spritesheet,
                tile.x,
                tile.y,
                tile.width,
                tile.height,
                0,
                0,
                destinationWidth,
                destinationHeight,
            );
        } else {
            this.context.drawImage(
                this.spritesheet,
                tile.x,
                tile.y,
                tile.width,
                tile.height,
                destinationX,
                destinationY,
                destinationWidth,
                destinationHeight,
            );
        }

        this.context.restore();
    }

    drawEyes({ shape, color }) {
        const eyesConfig =
            this.builderConfig?.layers?.eyes;

        const eyeConfig =
            eyesConfig?.shapes?.[shape];

        const eyeColor =
            eyesConfig?.colors?.[color];

        if (!eyeConfig || !eyeColor) {
            console.warn(
                'Configuration des yeux introuvable',
                { shape, color, eyesConfig },
            );

            return;
        }

        const {
            centerX = this.canvas.width / 2,
            centerY = this.canvas.height / 2,
            gap = 38,
        } = eyesConfig.position ?? {};

        const drawEye = (x, y) => {
            const width = eyeConfig.width;
            const height = eyeConfig.height;
            const radius = eyeConfig.radius;

            this.context.save();

            this.context.beginPath();

            this.roundedRect(
                x - width / 2,
                y - height / 2,
                width,
                height,
                radius,
            );

            this.context.fillStyle = '#f8fafc';
            this.context.fill();

            this.context.beginPath();
            this.context.arc(
                x,
                y,
                Math.min(width, height) * 0.28,
                0,
                Math.PI * 2,
            );

            this.context.fillStyle = eyeColor;
            this.context.fill();

            this.context.beginPath();
            this.context.arc(
                x,
                y,
                Math.min(width, height) * 0.12,
                0,
                Math.PI * 2,
            );

            this.context.fillStyle = '#111827';
            this.context.fill();

            this.context.restore();
        };

        drawEye(
            centerX - gap / 2,
            centerY,
        );

        drawEye(
            centerX + gap / 2,
            centerY,
        );
    }

    roundedRect(x, y, width, height, radius) {
        const r = Math.min(radius, width / 2, height / 2);

        this.context.moveTo(x + r, y);
        this.context.lineTo(x + width - r, y);
        this.context.quadraticCurveTo(
            x + width,
            y,
            x + width,
            y + r,
        );
        this.context.lineTo(x + width, y + height - r);
        this.context.quadraticCurveTo(
            x + width,
            y + height,
            x + width - r,
            y + height,
        );
        this.context.lineTo(x + r, y + height);
        this.context.quadraticCurveTo(
            x,
            y + height,
            x,
            y + height - r,
        );
        this.context.lineTo(x, y + r);
        this.context.quadraticCurveTo(
            x,
            y,
            x + r,
            y,
        );
    }

    drawNose({ shape }) {
        const noseConfig =
            this.builderConfig?.layers?.nose;

        const shapeConfig =
            noseConfig?.shapes?.[shape];

        if (!shapeConfig) {
            console.warn(
                'Configuration du nez introuvable',
                { shape, noseConfig },
            );

            return;
        }

        const {
            centerX = this.canvas.width / 2,
            centerY = this.canvas.height / 2,
        } = noseConfig.position ?? {};

        const {
            width,
            height,
            type,
        } = shapeConfig;

        const color = noseConfig.color ?? '#9d704c';

        this.context.save();
        this.context.imageSmoothingEnabled = false;
        this.context.fillStyle = color;
        this.context.strokeStyle = color;
        this.context.lineWidth = 3;
        this.context.lineCap = 'square';
        this.context.lineJoin = 'miter';

        switch (type) {
            case 'vertical':
                this.context.fillRect(
                    Math.round(centerX - width / 2),
                    Math.round(centerY - height / 2),
                    width,
                    height,
                );

                this.context.fillRect(
                    Math.round(centerX - width / 2 - 2),
                    Math.round(centerY + height / 2 - 2),
                    width + 4,
                    3,
                );
                break;

            case 'wide':
                this.context.fillRect(
                    Math.round(centerX - 2),
                    Math.round(centerY - height / 2),
                    4,
                    height,
                );

                this.context.fillRect(
                    Math.round(centerX - width / 2),
                    Math.round(centerY + height / 2 - 3),
                    width,
                    3,
                );
                break;

            case 'upturned':
                this.context.beginPath();

                this.context.moveTo(
                    centerX,
                    centerY - height / 2,
                );

                this.context.lineTo(
                    centerX,
                    centerY + height / 2,
                );

                this.context.lineTo(
                    centerX + width / 2,
                    centerY + height / 2 - 3,
                );

                this.context.stroke();
                break;

            case 'straight':
            default:
                this.context.fillRect(
                    Math.round(centerX - width / 2),
                    Math.round(centerY - height / 2),
                    width,
                    height,
                );

                this.context.fillRect(
                    Math.round(centerX - width / 2),
                    Math.round(centerY + height / 2 - 2),
                    width + 3,
                    3,
                );
                break;
        }

        this.context.restore();
    }

    drawMouth({ shape }) {
        const mouthConfig =
            this.builderConfig?.layers?.mouth;

        const shapeConfig =
            mouthConfig?.shapes?.[shape];

        if (!shapeConfig) {
            console.warn(
                'Configuration de la bouche introuvable',
                { shape, mouthConfig },
            );

            return;
        }

        const {
            centerX = this.canvas.width / 2,
            centerY = this.canvas.height / 2,
        } = mouthConfig.position ?? {};

        const {
            width,
            height,
            type,
        } = shapeConfig;

        const color =
            mouthConfig.color ?? '#7f3f3f';

        const innerColor =
            mouthConfig.innerColor ?? '#3a171b';

        this.context.save();
        this.context.imageSmoothingEnabled = false;
        this.context.fillStyle = color;
        this.context.strokeStyle = color;
        this.context.lineWidth = 3;
        this.context.lineCap = 'square';

        switch (type) {
            case 'thin':
                this.context.fillRect(
                    Math.round(centerX - width / 2),
                    Math.round(centerY - height / 2),
                    width,
                    height,
                );
                break;

            case 'full':
                this.context.beginPath();

                this.context.ellipse(
                    centerX,
                    centerY,
                    width / 2,
                    height / 2,
                    0,
                    0,
                    Math.PI * 2,
                );

                this.context.fill();

                this.context.fillStyle = innerColor;

                this.context.fillRect(
                    Math.round(centerX - width / 2 + 4),
                    Math.round(centerY - 1),
                    width - 8,
                    2,
                );
                break;

            case 'smile':
                this.context.beginPath();

                this.context.moveTo(
                    centerX - width / 2,
                    centerY - height / 3,
                );

                this.context.quadraticCurveTo(
                    centerX,
                    centerY + height,
                    centerX + width / 2,
                    centerY - height / 3,
                );

                this.context.stroke();
                break;

            case 'neutral':
            default:
                this.context.fillRect(
                    Math.round(centerX - width / 2),
                    Math.round(centerY - height / 2),
                    width,
                    height,
                );
                break;
        }

        this.context.restore();
    }

    /**
     * Dessine un calque composé d’une ou plusieurs tuiles.
     *
     * @param {{
     *     tiles?: Array<{
     *         tile: string,
     *         x?: number,
     *         y?: number,
     *         scale?: number,
     *         flipX?: boolean,
     *         opacity?: number
     *     }>
     * }} layer
     */
    drawLayer(layer) {
        if (!layer || !Array.isArray(layer.tiles)) {
            return;
        }

        layer.tiles.forEach((tile) => {
            this.drawTile(tile.tile, tile);
        });
    }

    /**
     * @param {Array<object>} layers
     * @param {boolean} clear
     */
    render(layers, clear = true) {
        if (clear) {
            this.clear();
        }

        layers
            .filter(Boolean)
            .forEach((layer) => this.drawLayer(layer));
    }

    /**
     * @param {string} type
     * @param {number|undefined} quality
     */
    toDataUrl(type = 'image/png', quality = undefined) {
        return this.canvas.toDataURL(type, quality);
    }

    /**
     * Alternative plus efficace à toDataURL().
     *
     * @param {string} type
     * @param {number} quality
     * @returns {Promise<Blob>}
     */
    toBlob(type = 'image/png', quality = 1) {
        return new Promise((resolve, reject) => {
            this.canvas.toBlob(
                (blob) => {
                    if (!blob) {
                        reject(
                            new Error(
                                'Impossible de convertir le canvas en image.',
                            ),
                        );

                        return;
                    }

                    resolve(blob);
                },
                type,
                quality,
            );
        });
    }
}
