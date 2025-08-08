# Skeletor

Skeletor is een WordPress-thema gebaseerd op [Timber](https://timber.github.io/) en de Twig templating engine. Het thema biedt een component-gebaseerde structuur waarmee je snel maatwerk websites kunt opzetten.

## Installatie

1. Plaats de map `skeletor` in `wp-content/themes` of installeer het thema via Composer.
2. Voer vanuit de themamap `composer install` uit om de PHP-afhankelijkheden te installeren.
3. Activeer het thema in het WordPress-dashboard onder **Weergave → Thema's**.

## Structuur

- `assets/` – bronbestanden voor JavaScript en SCSS.
- `components/` – herbruikbare PHP/Twig/SCSS componenten.
- `static/` – gecompileerde frontend-bestanden.
- `views/` – Twig-sjablonen voor de WordPress template hierarchy.

## Custom componenten
De componenten in `components/` worden automatisch geladen en zijn beschikbaar als Twig-functies. Hieronder een overzicht van de beschikbare componenten met voorbeeldgebruik.

### Accordion
```twig
{{ accordion(item.accordion_items, {
  id_prefix: 'acc',
  icon: 'plus',
  first_item_open: true
}) }}
```
- Items: array met `titel` en `tekst`.
- Opties: `id_prefix`, `icon`, `icon_position`, `heading_level`, `heading_class`, `icon_weight`, `first_item_open`.

### Button
```twig
{{ button(item.link, {
  style: 'outline-primary',
  size: 'lg',
  icon: 'arrow-right',
  icon_position: 'after'
}) }}
```
- Werkt met een ACF link‑veld.
- Opties: `style`, `size`, `icon`, `icon_position`, `icon_style`, `target`, `class`.

### Filter
Definieer filters in PHP en render ze in Twig:
```twig
{{ filter(filters.uren, {
  limit_options: 3,
  show_option_counts: true
}) }}
{{ sort_select(filters.sort) }}
```
- Ondersteunt `select`, `checkbox`, `radio`, `buttons` en `range`.
- `sort_select` voegt een standaard sorteermenu toe.

### Heading
```twig
{{ heading(item.titel, {
  level: 'h2',
  class: 'mb-3',
  inview_animation: 'typewriter'
}) }}
```

### Image
```twig
{{ image(item.afbeelding, {
  ratio: '16x9',
  figure_class: 'rounded shadow',
  show_caption: true,
  caption_position: 'below-right'
}) }}
```

### Social media links
```twig
{{ social_media_links({
  facebook: { url: 'https://facebook.com', suffix: 'f' },
  instagram: { url: 'https://instagram.com' }
}) }}
```
- Opties: `show_icons`, `class`.

### Swiper
```twig
{{ swiper(posts, {
  slidesPerView: 3,
  spaceBetween: 10,
  arrows: true,
  dots: false
}, 'tease.twig') }}
```
- Opties o.a. `direction`, `loop`, `slidesPerView`, `spaceBetween`, `autoplay`, `arrows`, `dots`, `breakpoints`.

### Text
```twig
{{ text(item.tekst, {
  tag: 'p',
  class: 'lead',
  max_length: 120
}) }}
```
- Opties: `class`, `tag`, `style`, `max_length`, `inview_animation`.

## Tests
Draai de tests met:

```bash
composer test
```

## Licentie
Dit project valt onder de MIT-licentie. Zie het bestand `LICENSE` voor de volledige tekst.
