import { useEffect } from 'react'

const SITE_URL = 'https://heartofjerome.com'
const SITE_NAME = 'The Heart of Jerome'
const DEFAULT_IMAGE = `${SITE_URL}/icon-512.png`

// Set or create a <meta> tag, keyed by name= or property=.
function setMeta(attr, key, content) {
  let el = document.head.querySelector(`meta[${attr}="${key}"]`)
  if (!el) {
    el = document.createElement('meta')
    el.setAttribute(attr, key)
    document.head.appendChild(el)
  }
  el.setAttribute('content', content)
}

function setCanonical(href) {
  let el = document.head.querySelector('link[rel="canonical"]')
  if (!el) {
    el = document.createElement('link')
    el.setAttribute('rel', 'canonical')
    document.head.appendChild(el)
  }
  el.setAttribute('href', href)
}

/**
 * Per-page SEO. Renders nothing; updates the document head on mount/route change.
 *   title       — full <title> text
 *   description — meta description + og/twitter description
 *   path        — canonical path, e.g. "/ideas" (defaults to current location)
 *   image       — absolute og/twitter image URL
 */
export default function Seo({ title, description, path, image = DEFAULT_IMAGE }) {
  useEffect(() => {
    const url = SITE_URL + (path ?? window.location.pathname)

    if (title) document.title = title
    if (description) setMeta('name', 'description', description)
    setCanonical(url)

    setMeta('property', 'og:title', title)
    setMeta('property', 'og:description', description)
    setMeta('property', 'og:url', url)
    setMeta('property', 'og:image', image)
    setMeta('property', 'og:site_name', SITE_NAME)
    setMeta('name', 'twitter:title', title)
    setMeta('name', 'twitter:description', description)
    setMeta('name', 'twitter:image', image)
  }, [title, description, path, image])

  return null
}
