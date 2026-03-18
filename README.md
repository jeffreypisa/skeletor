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

## Componenten
De componenten in `components/` worden automatisch geregistreerd als Twig-functies.

Beschikbare functies:
- `accordion()`
- `breadcrumbs()`
- `button()`
- `filter()`
- `sort_select()`
- `active_filters()`
- `heading()`
- `icon()`
- `image()`
- `inpage_nav()`
- `swiper()`
- `text()`

### Accordion (`accordion(items, args = {})`)
Gebruik voor FAQ’s en uitklapcontent.

```twig
{{ accordion(item.accordion_items, {
  id_prefix: 'faq',
  icon: 'plus',
  icon_position: 'before',
  heading_level: 'h3',
  heading_class: 'mb-0',
  icon_weight: 'solid',
  first_item_open: true
}) }}
```

Mogelijkheden:
- `items`: array met `titel` en `tekst` (mag ook WP/Timber posts bevatten).
- `id_prefix` (default `accordion`)
- `icon`: `chevron` of `plus`
- `icon_position`: `before` of `after`
- `heading_level`: `h1` t/m `h6`
- `heading_class`: extra class(es)
- `icon_weight`: icon style map (bijv. `light`, `solid`)
- `first_item_open`: eerste item open bij load

### Breadcrumbs (`breadcrumbs()`)
Automatische kruimelpadnavigatie op basis van huidige pagina.

```twig
{{ breadcrumbs() }}
```

Mogelijkheden:
- Geen argumenten nodig.
- Werkt voor home, archives, search en singular pagina’s.
- Is ook beschikbaar in context als `breadcrumbs`.

### Button (`button(button_field, params = {})`)
Rendert een knop op basis van een ACF Link veld (`url`, `title`, `target`).

```twig
{{ button(item.link, {
  title: 'Meer informatie',
  style: 'outline-primary',
  size: 'lg',
  icon: 'arrow-right',
  icon_position: 'after',
  icon_style: 'solid',
  target: '_blank',
  class: 'mt-3'
}) }}
```

Mogelijkheden:
- `params` mag ook een string zijn: `{{ button(item.link, 'primary') }}`.
- `title` (default ACF title of `Klik hier`)
- `url` (default ACF url)
- `style` (default `primary`)
- `size` (`sm`, `lg`, ...)
- `target` (default ACF target of `_self`)
- `icon` (Font Awesome naam zonder `fa-`)
- `icon_position`: `before` of `after`
- `icon_style`: `solid`, `regular`, `light`, `brands`
- `class`: extra class(es)

### Filter (`filter(data, args = {})`)
Filtercomponent voor taxonomy/meta/date/post type/author.

```php
$context['filters']['topic'] = [
  'name' => 'category',
  'label' => 'Onderwerp',
  'type' => 'checkbox',
  'source' => 'taxonomy',
  'show_hierarchy' => true,
  'sort_options' => 'count_desc',
  'hide_empty_options' => true,
  'url_sync' => ['enabled' => true, 'mode' => 'path'],
];
```

```twig
{{ filter(filters.topic, {
  layout: 'vertical',
  show_field_label: true,
  show_option_counts: true,
  limit_options: 6,
  option_list_expand_label: 'Meer opties',
  option_list_collapse_label: 'Minder opties',
  placeholder: 'Maak een keuze',
  date_format: 'd-m-Y'
}) }}
```

Mogelijkheden (data):
- `name`: veldnaam/taxonomy/inputnaam
- `label`: labeltekst
- `type`: `select`, `checkbox`, `radio`, `buttons`, `range`, `date`, `date_range`
- `source`: `field`, `meta`, `taxonomy`, `post_date`, `post_type`, `user`, `author`
- `options`: handmatige opties (`label => value`), anders auto-ophalen
- `sort_options`: `asc`, `desc`, `count_asc`, `count_desc`, `none`
- `hide_empty_options`: alleen opties met resultaten
- `show_hierarchy`: taxonomy als boomstructuur
- `date_format`: datum output/input formaat (default `d-m-Y`)
- `url_sync`:
  - `true/false`
  - of array met `enabled`, `mode` (`query`/`path`), `base_url`, `path_prefix`

Mogelijkheden (presentatie via `args`):
- `layout`: `vertical` of `horizontal`
- `show_field_label`: label tonen/verbergen
- `show_option_counts`: aantallen per optie tonen
- `limit_options`: eerst X tonen met toggle knop
- `option_list_expand_label` / `option_list_collapse_label`
- `placeholder`: select placeholder
- `date_format`: datumweergave
- Extra voor `buttons`: `button_class`, `all_label`, `show_all_button`

Belangrijk gedrag:
- `range` krijgt automatisch `min/max` op basis van data als niet meegegeven.
- `date` en `date_range` vullen automatisch oudste/nieuwste datum in als GET-waarden ontbreken.

### Sort Select (`sort_select(value, args = {})`)
Gestandaardiseerde sorteer-dropdown.

```twig
{{ sort_select(filters.sort.value, {
  id: 'sort',
  name: 'sort',
  label: 'Sorteer op',
  show_label: true,
  layout: 'horizontal',
  url_sync: true,
  options: {
    relevance: 'Relevantie',
    date_desc: 'Nieuwste eerst',
    date_asc: 'Oudste eerst',
    title_asc: 'Titel A-Z',
    title_desc: 'Titel Z-A'
  }
}) }}
```

Mogelijkheden:
- `id`, `name`, `label`, `value`
- `options` (key => label)
- `show_label`
- `layout`: `vertical` of `horizontal`
- `url_sync`: neem sortering mee in URL

### Active Filters (`active_filters(args = {})`)
Toont actieve filters als chips met “wis alles” en “toon meer”.

```twig
{{ active_filters({
  label: 'Actieve filters',
  max_visible: 5,
  show_count: true,
  show_clear_all: true,
  show_more_label: '+%d meer',
  show_less_label: 'Toon minder',
  clear_all_label: 'Wis alles',
  search_label: 'Zoekterm',
  exclude_filters: ['sort'],
  chip_class: 'btn btn-sm btn-outline-primary'
}) }}
```

Mogelijkheden:
- `max_visible`
- `show_clear_all`
- `show_count`
- `show_more_label`
- `show_less_label`
- `clear_all_label`
- `search_label`
- `label`
- `exclude_filters` (array of comma-separated string)
- `chip_class`

### Heading (`heading(text, options = {})`)
Heading component met optionele in-view animaties.

```twig
{{ heading(item.titel, {
  level: 'h2',
  class: 'mb-3',
  inview_animation: 'word-rise',
  inview_animation_speed: 0.9
}) }}
```

Mogelijkheden:
- `level` (default `h2`)
- `class`
- `inview_animation` (bijv. `fade-in`, `typewriter`, `word-rise`, etc.)
- `inview_animation_speed` (0.1 - 10)

### Icon (`icon(args)`)
SVG icon component op basis van `components/library/icon/library/...`.

```twig
{{ icon({
  icon: 'arrow-right',
  style: 'light',
  library: 'fontawesome',
  class: 'my-icon',
  icon_wrapper_height: 20,
  icon_wrapper_width: 20,
  icon_class: 'text-primary',
  title: 'Meer info',
  title_position: 'right',
  title_level: 'span',
  title_class: 'small',
  gap: 8,
  url: '/contact',
  target: 'self',
  color_primary: '#0d6efd',
  color_secondary: '#6c757d'
}) }}
```

Mogelijkheden:
- `icon` (vereist, bestandsnaam zonder `.svg`)
- `style` (bijv. `light`, `solid`, `regular`, `brands`, `duotone` als aanwezig)
- `library` (default `fontawesome`)
- `class`
- `icon_wrapper_height`, `icon_wrapper_width` (`int`, `px`, `rem`, `auto`)
- `icon_wrapper_class`, `icon_class`
- `title`, `title_position` (`left/right/top/bottom`), `title_level`, `title_class`
- `gap`
- `url` + `target` (`self` of `blank`)
- `color_primary`, `color_secondary` (voor duotone varianten)

### Image (`image(image_field, options = {})`)
Afbeelding met ratio, caption, animatie en optionele popup-link.

```twig
{{ image(item.afbeelding, {
  ratio: '16x9',
  figure_class: 'rounded overflow-hidden',
  img_class: 'rounded',
  object_fit: 'cover',
  lazyload: true,
  style: 'max-width: 720px;',
  show_caption: true,
  caption_position: 'below-right',
  inview_animation: 'zoom-in',
  inview_animation_speed: 1.1,
  popup: true,
  popup_type: 'image',
  popup_title: item.afbeelding.caption,
  popup_class: 'gallery-item',
  popup_attrs: {
    'data-gallery': 'project'
  }
}) }}
```

Mogelijkheden:
- `ratio` (bijv. `16x9`, `4x3`)
- `figure_class`, `img_class`
- `object_fit` (`cover`, `contain`, ...)
- `lazyload` (default `true`)
- `style` (inline style)
- `show_caption`
- `caption_position`: `on-left`, `on-right`, `below-left`, `below-right`
- `inview_animation` + `inview_animation_speed`
- `overlay_direction` (voor animatie/CSS hook)
- Popupopties: `popup`, `popup_url`, `popup_type`, `popup_title`, `popup_class`, `popup_attrs`

### Inpage Nav (`inpage_nav(args = {})`)
Auto in-page navigatie (desktop + mobiel select) op basis van headings en optioneel parent links.

```twig
{{ inpage_nav({
  id: post.id,
  title: 'Inhoud',
  mode: 'all',
  parent_links_mode: 'taxonomy',
  parent_links_category: 'category',
  parent_icon: 'chevron',
  parent_icon_size: 12
}) }}
```

Mogelijkheden:
- `id` (default current post id)
- `title` (default `Inhoud`)
- `mode`: `all`, `nav`, `mobile`
- `parent_links`: handmatige array met `{id,title,url,is_current}`
- `parent_links_mode`: `taxonomy` of `siblings`
- `current_parent_id`
- `parent_links_category`: taxonomy naam voor auto-parent-links
- `parent_icon`: `arrow` of `chevron`
- `parent_icon_size`

### Swiper (`swiper(slides, settings = {}, template = 'tease.twig')`)
Wrapper voor [Swiper.js](https://swiperjs.com/swiper-api) met sensible defaults.

```twig
{{ swiper(posts, {
  slidesPerView: 1,
  spaceBetween: 16,
  loop: false,
  autoplay: false,
  arrows: true,
  dots: true,
  arrowPrevIcon: 'arrow-left',
  arrowNextIcon: 'arrow-right',
  arrowIconStyle: 'light',
  class: 'my-swiper',
  mobileListGap: '1rem',
  breakpoints: {
    768: { slidesPerView: 2, spaceBetween: 20 },
    1200: { slidesPerView: 3, spaceBetween: 24 }
  }
}, 'tease.twig') }}
```

Mogelijkheden:
- `direction`, `loop`, `slidesPerView`, `spaceBetween`, `loopAdditionalSlides`
- `centeredSlides`, `speed`, `autoplay`
- `arrows`, `dots`
- `arrowPrevIcon`, `arrowNextIcon`, `arrowIconStyle`
- `mobileListGap` (CSS variable voor mobiele lijstweergave)
- `class`
- `swiper_id` en `navigation` (overschrijfbaar, standaard automatisch gegenereerd)
- Alle extra native Swiper settings (zoals `breakpoints`)

### Text (`text(text, options = {})`)
Tekstcomponent met optionele truncatie, HTML-ondersteuning en animatie.

```twig
{{ text(item.tekst, {
  tag: 'p',
  class: 'lead mb-3',
  style: 'max-width: 65ch;',
  max_length: 220,
  inview_animation: 'line-reveal',
  inview_animation_speed: 1
}) }}
```

Mogelijkheden:
- `class`
- `tag` (default `p`, alleen gebruikt als input geen HTML bevat)
- `style`
- `max_length` (voegt `...` toe)
- `inview_animation`
- `inview_animation_speed` (0.1 - 10)

Opmerking:
- Wanneer `text` al HTML bevat, wordt die HTML direct gerenderd.
- Lege `<p>` tags worden automatisch verwijderd.

## Tests
Draai de tests met:

```bash
composer test
```

## Licentie
Dit project valt onder de MIT-licentie. Zie het bestand `LICENSE` voor de volledige tekst.
