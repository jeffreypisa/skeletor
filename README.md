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
- Opties: `title`, `url`, `style`, `size`, `target`, `icon`, `icon_position`, `icon_style`, `class`.

### Filter
Definieer filters in PHP en render ze in Twig:
```twig
{{ filter(filters.uren, {
  limit_options: 3,
  option_list_expand_label: 'Meer opties',
  option_list_collapse_label: 'Minder opties',
  placeholder: 'Maak een keuze',
  layout: 'horizontal',
  show_field_label: true,
  show_option_counts: true
}) }}
{{ sort_select(filters.sort) }}
```
- Ondersteunt `select`, `checkbox`, `radio`, `buttons`, `range`, `date` en `date_range`.
- Data-opties per filter: `name`, `label`, `type`, `source`, `options`, `value`, `sort_options`, `hide_empty_options`.
- Presentatie-opties: `limit_options`, `option_list_expand_label`, `option_list_collapse_label`, `placeholder`, `layout`, `show_field_label`, `show_option_counts`, `date_format`.
- `sort_select` accepteert `id`, `name`, `label`, `value` en voegt een standaard sorteermenu toe.
- Voor type `buttons` zijn extra opties beschikbaar: `button_class`, `all_label`, `show_all_button`.

Voor een datumfilter kan gefilterd worden op publicatiedatum (`source: 'post_date'`) of op een ACF-datumveld (`source: 'acf'`).

```php
// Enkel datumveld (ACF)
$context['filters']['event_date'] = [
  'name'   => 'event_date',
  'label'  => 'Datum',
  'type'   => 'date',
  'source' => 'acf',
];

// Van/tot op publicatiedatum
$context['filters']['published'] = [
  'name'   => 'post_date',
  'label'  => 'Periode',
  'type'   => 'date_range',
  'source' => 'post_date',
];
```

Datumvelden zonder opgegeven waardes worden automatisch ingevuld met de oudste en nieuwste beschikbare datum.

### Heading
```twig
{{ heading(item.titel, {
  level: 'h2',
  class: 'mb-3',
  inview_animation: 'typewriter'
}) }}
```
- Opties: `level`, `class`, `inview_animation`.

### Image
```twig
{{ image(item.afbeelding, {
  ratio: '16x9',
  figure_class: 'rounded shadow',
  show_caption: true,
  caption_position: 'below-right'
}) }}
```
- Opties: `ratio`, `figure_class`, `img_class`, `object_fit`, `lazyload`, `style`, `show_caption`, `caption_position`, `inview_animation`, `overlay_direction`.

### Social media links
```twig
{{ social_media_links({
  facebook: { url: 'https://facebook.com', suffix: 'f' },
  instagram: { url: 'https://instagram.com' }
}) }}
```
- Opties: `show_icons`, `class`.
- Elke link vereist `url` en optionele `suffix` voor een alternatieve iconvariant.

### Swiper
```twig
{{ swiper(posts, {
  slidesPerView: 3,
  spaceBetween: 10,
  arrows: true,
  dots: false
}, 'tease.twig') }}
```
- Opties: `direction`, `loop`, `slidesPerView`, `spaceBetween`, `loopAdditionalSlides`, `centeredSlides`, `speed`, `autoplay`, `arrows`, `dots`, `arrowPrevIcon`, `arrowNextIcon`, `arrowIconStyle`, `class`, `swiper_id`, `navigation.nextEl`, `navigation.prevEl`, `navigation.disabledClass`.
- Extra [Swiper.js](https://swiperjs.com/swiper-api) instellingen zoals `breakpoints` kunnen worden meegegeven in de `settings` array.

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
