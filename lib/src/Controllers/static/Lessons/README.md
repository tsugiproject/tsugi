# Lessons controller static files

- `tsugi-kaltura-video.js` — Lit web component `<tsugi-kaltura-video>` for
  Kaltura lecture playback (play trigger + modal with playlist tab links).

Loaded from `Tsugi\UI\Lessons::footer()` on single-module lesson pages via
`StaticFiles::url('Lessons', 'tsugi-kaltura-video.js')`.
