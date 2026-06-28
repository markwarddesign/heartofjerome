// Inline icons (currentColor) — no icon font, no network request.
const PATHS = {
  heart: 'M12 21s-7.5-4.6-10-9.3C.6 8.4 2.3 5 5.5 5c1.9 0 3.3 1 4.5 2.5C11.2 6 12.6 5 14.5 5 17.7 5 19.4 8.4 22 11.7 19.5 16.4 12 21 12 21z',
  star: 'M12 3l2.9 5.9 6.5.9-4.7 4.6 1.1 6.5L12 18l-5.8 3.4 1.1-6.5L2.6 9.8l6.5-.9z',
  sprout: 'M12 22V11M12 11c0-3 2-6 6-6 0 3-2 6-6 6zM12 13C9 13 6 11 6 7c3 0 6 2 6 6z',
  arrow: 'M7 17 17 7M9 7h8v8',
  phone: 'M5 4h4l2 5-3 2a12 12 0 0 0 5 5l2-3 5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z',
  mail: 'M3 5h18v14H3zM3 7l9 6 9-6',
  upload: 'M12 16V4m0 0 4 4m-4-4-4 4M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2',
  check: 'M20 6 9 17l-5-5',
  info: 'M12 11v5M12 8h.01',
  home: 'M3 11l9-7 9 7M5 10v9a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-9',
  school: 'm3 9 9-5 9 5-9 5-9-5zM7 11v5c0 1 2.2 2.5 5 2.5s5-1.5 5-2.5v-5',
  users: 'M3 20c0-3 2.7-5 6-5s6 2 6 5M21 20c0-2.3-1.5-4-3.5-4.7',
  book: 'M4 5a2 2 0 0 1 2-2h12v16H6a2 2 0 0 0-2 2zM4 19a2 2 0 0 0 2 2h12',
  menu: 'M4 7h16M4 12h16M4 17h16',
  close: 'M6 6l12 12M18 6 6 18',
}

const CIRCLES = {
  info: [{ cx: 12, cy: 12, r: 9 }],
  users: [{ cx: 9, cy: 8, r: 3 }],
}

export default function Icon({ name, className = 'icon', filled = false, ...rest }) {
  const d = PATHS[name] || ''
  const circles = CIRCLES[name] || []
  return (
    <svg
      className={className}
      viewBox="0 0 24 24"
      fill={filled ? 'currentColor' : 'none'}
      stroke={filled ? 'none' : 'currentColor'}
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
      {...rest}
    >
      {circles.map((c, i) => (
        <circle key={i} cx={c.cx} cy={c.cy} r={c.r} />
      ))}
      {d && <path d={d} />}
    </svg>
  )
}
