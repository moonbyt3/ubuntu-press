#!/usr/bin/env python3
"""
Generates assets/css/editor-dark.css from WordPress core's block editor styles.

Most of the editor UI hardcodes light greys instead of using CSS variables, so
this copies every rule that sets one of those colours, swaps the colour for its
dark counterpart and scopes the rule to body.wpskin-admin--dark with :where(),
which keeps core's specificity (hover / pressed states still win).

Usage (re-run after WordPress updates):
    python3 bin/build-editor-dark.py /path/to/wordpress
"""

import re
import sys
from pathlib import Path

SOURCES = ['components', 'block-editor', 'editor', 'edit-post']
SCOPE = ':where(.wpskin-admin--dark)'

# Light editor greys -> dark equivalents, per property group.
BACKGROUNDS = {
	'#fff': '#1e1e1e',
	'#ffffff': '#1e1e1e',
	'white': '#1e1e1e',
	'#fcfcfc': '#232323',
	'#f9f9f9': '#232323',
	'#f8f8f8': '#232323',
	'#f6f7f7': '#232323',
	'#f4f4f4': '#262626',
	'#f0f0f0': '#2a2a2a',
	'#f0f0f1': '#2a2a2a',
	'#eee': '#2f2f2f',
	'#ededed': '#2f2f2f',
	'#e0e0e0': '#3d3d3d',
	'#ddd': '#3d3d3d',
	# Dark fills (tooltips, pressed buttons) would vanish on a dark surface.
	'#1e1e1e': '#454545',
	'#2f2f2f': '#454545',
	'#000': '#454545',
}

TEXT = {
	'#1e1e1e': '#e6e6e6',
	'#2f2f2f': '#e0e0e0',
	'#000': '#e6e6e6',
	'#3c434a': '#d0d0d0',
	'#50575e': '#bdbdbd',
	'#757575': '#a7a7a7',
}

BORDERS = {
	'#fff': '#1e1e1e',
	'#f0f0f0': '#2f2f2f',
	'#e0e0e0': '#3d3d3d',
	'#ddd': '#3d3d3d',
	'#ccc': '#4a4a4a',
	'#1e1e1e': '#6b6b6b',
	'#2f2f2f': '#6b6b6b',
}

def group(prop):
	if prop in ('background', 'background-color'):
		return BACKGROUNDS
	if prop in ('color', 'fill', 'outline-color'):
		return TEXT
	if prop.startswith('border') or prop in ('box-shadow', 'outline'):
		return BORDERS
	return None


def swap(value, table):
	def repl(m):
		return table.get(m.group(0).lower(), m.group(0))
	# Only bare colour tokens; leaves var(..., #fff) fallbacks to the variables we set.
	if 'var(' in value:
		return value
	return re.sub(r'#[0-9a-fA-F]{3,6}\b|\bwhite\b', repl, value)


def scope(selector):
	selector = selector.strip()
	if selector.startswith(('html', ':root')):
		return None
	if selector.startswith('body'):
		return 'body' + SCOPE + selector[4:]
	return SCOPE + ' ' + selector


def convert_block(css):
	"""Returns converted rules for a flat block of CSS (no nested at-rules)."""
	out = []
	for m in re.finditer(r'([^{}]+)\{([^{}]*)\}', css):
		selectors, body = m.group(1), m.group(2)
		decls = []
		for decl in body.split(';'):
			if ':' not in decl:
				continue
			prop, value = decl.split(':', 1)
			prop = prop.strip().lower()
			table = group(prop)
			if not table:
				continue
			new = swap(value.strip(), table)
			if new != value.strip():
				decls.append(f'\t{prop}: {new};')
		if not decls:
			continue
		scoped = [s for s in (scope(s) for s in split_selectors(selectors)) if s]
		if scoped:
			out.append(',\n'.join(scoped) + ' {\n' + '\n'.join(decls) + '\n}')
	return out


def split_selectors(selectors):
	"""Splits on top-level commas (not those inside :is(), :not(), etc.)."""
	parts, depth, current = [], 0, ''
	for ch in selectors:
		if ch == '(':
			depth += 1
		elif ch == ')':
			depth -= 1
		if ch == ',' and depth == 0:
			parts.append(current)
			current = ''
		else:
			current += ch
	parts.append(current)
	return [p.strip() for p in parts if p.strip()]


def convert(css):
	css = re.sub(r'/\*.*?\*/', '', css, flags=re.S)
	out, pos = [], 0
	# Walk top level, recursing into @media / @supports and skipping @keyframes / @font-face.
	for m in re.finditer(r'@(media|supports|keyframes|-webkit-keyframes|font-face|container)[^{]*\{', css):
		if m.start() < pos:
			continue
		out += convert_block(css[pos:m.start()])
		depth, i = 1, m.end()
		while depth:
			depth += {'{': 1, '}': -1}.get(css[i], 0)
			i += 1
		if m.group(1) in ('media', 'supports', 'container'):
			inner = convert(css[m.end():i - 1])
			if inner:
				out.append(m.group(0).strip() + '\n' + '\n'.join(inner) + '\n}')
		pos = i
	out += convert_block(css[pos:])
	return out


def main():
	if len(sys.argv) != 2:
		sys.exit(__doc__)
	wp = Path(sys.argv[1])
	version = re.search(r"\$wp_version = '([^']+)'", (wp / 'wp-includes/version.php').read_text()).group(1)
	plugin = Path(__file__).resolve().parent.parent

	parts = [
		'/*',
		f' * WPSkin dark theme for the block editor UI. Generated from WordPress {version}',
		' * by bin/build-editor-dark.py; do not edit by hand.',
		' */',
	]
	for name in SOURCES:
		rules = convert((wp / f'wp-includes/css/dist/{name}/style.css').read_text())
		parts.append(f'\n/* {name} */\n' + '\n\n'.join(rules))

	target = plugin / 'assets/css/editor-dark.css'
	target.write_text('\n'.join(parts) + '\n')
	print(f'Wrote {target} ({sum(1 for _ in target.open())} lines)')


if __name__ == '__main__':
	main()
