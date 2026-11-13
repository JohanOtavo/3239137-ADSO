#!/usr/bin/env node
/**
 * T-18 - Fails when a Spanish identifier or comment reaches the source.
 *
 * Article XI says the code is written in English and the messages the user
 * reads are written in Spanish. Both halves matter, so this check reads
 * identifiers and comments and deliberately skips string literals: flagging
 * the Spanish inside a message would make the check impossible to keep green,
 * and the messages are the one place Spanish belongs.
 *
 * It is a plain script with no dependencies, because a guard that needs an
 * install step is a guard that gets skipped.
 */

import { readdir, readFile, stat } from 'node:fs/promises';
import { join, relative, extname } from 'node:path';
import process from 'node:process';

const DEFAULT_ROOTS = ['../api', '../app', '../page', '../tool'];

const CODE_EXTENSIONS = new Set(['.php', '.js', '.mjs', '.cjs', '.jsx', '.ts', '.tsx', '.css', '.html']);

const SKIPPED_DIRECTORIES = new Set([
  'node_modules',
  'vendor',
  '.git',
  'dist',
  'build',
  'coverage',
  'storage',
  'bootstrap/cache',
  // The fixtures contain Spanish on purpose, to prove the guard bites. They
  // are reached only by the guard's own test, which names them explicitly.
  'fixtures',
]);

/**
 * Words that only appear in Spanish code. Short words like `de` or `la` are
 * left out on purpose: they collide with English identifiers and with base64,
 * and a guard that cries wolf is a guard somebody disables.
 */
const SPANISH_WORDS = [
  'actualizar', 'alquiler', 'alquileres', 'archivo', 'buscar', 'cantidad',
  'categoria', 'categorias', 'cliente', 'codigo', 'consulta', 'contrasena',
  'crear', 'datos', 'devolucion', 'devolver', 'eliminar', 'equipo', 'equipos',
  'estado', 'fecha', 'guardar', 'informe', 'listar', 'mensaje',
  'nombre', 'numero', 'obtener', 'pagina', 'prestamo', 'prestamos',
  'productor', 'productores', 'reporte', 'rol', 'tabla', 'usuario', 'usuarios',
  'validar', 'vencido', 'vencidos',
];

const SPANISH_PATTERN = new RegExp(`\\b(${SPANISH_WORDS.join('|')})\\b`, 'i');

/** This file names the words it looks for, so it cannot inspect itself. */
const SELF = 'check-language.mjs';

/** A letter that does not occur in English spelling is evidence on its own. */
const SPANISH_LETTERS = /[áéíóúñ¿¡]/i;

/**
 * Removes string literals, so the Spanish inside a message is not read as
 * Spanish inside the code. Interpolation is not unwrapped: whatever is left
 * between the quotes is replaced by an empty literal of the same kind.
 */
function withoutStringLiterals(source) {
  return source
    .replace(/'(?:[^'\\\n]|\\.)*'/g, "''")
    .replace(/"(?:[^"\\\n]|\\.)*"/g, '""')
    .replace(/`(?:[^`\\]|\\.)*`/g, '``')
    .replace(/<<<'?([A-Z_]+)'?[\s\S]*?^\1;/gm, 'HEREDOC;');
}

/**
 * In HTML, only the tags and the comments are code. The text between them is
 * prose a person reads, and the public site is written for Spanish readers, so
 * reading it here would be the same mistake as reading a message.
 */
function markupOnly(source) {
  return source
    .split(/\r?\n/)
    .map((line) => (line.match(/<[^>]*>/g) ?? []).join(' '))
    .join('\n');
}

/**
 * In JSX, the text between tags is prose a person reads, exactly like the text
 * of an HTML page and like the inside of a string literal. The screens are
 * written for Spanish readers, so this text is removed before the scan; the
 * code around it, attribute names and expressions in braces are kept.
 */
function withoutJsxText(source) {
  return source
    .split(/\r?\n/)
    .map((line) => {
      let stripped = line;
      let previous = null;

      /*
       * Repeated because the runs overlap, and bounded by braces as well as by
       * tags: JSX text is routinely cut in half by an expression, as in
       * "hasta {MAX} equipos por alquiler".
       */
      while (stripped !== previous) {
        previous = stripped;
        stripped = stripped
          .replace(/[>}][^<>{}]*[<{]/g, (run) => run[0] + run[run.length - 1])
          .replace(/}[^<>{}]*$/, '}');
      }

      // Text that ends at a closing tag or at an expression, when the tag that
      // opened it was on an earlier line: "Catalogo</h2>", "4 dias.{total}".
      stripped = stripped
        .replace(/^[^<>{}]*<\//, '<\/')
        .replace(/^[^<>{}();=]*{/, '{');

      /*
       * A line of pure JSX text, with no code on it at all. A comment is not
       * that: it is written for whoever reads the source, so it stays and is
       * scanned.
       */
      const bare = stripped.trim();
      const isComment = /^(\/\/|\/\*|\*|#)/.test(bare);
      const isStatement = /^(import|export|return|const|let|var)\b/.test(bare);

      if (!isComment && !isStatement && /^[^<>{}();=]*$/.test(bare)) {
        return '';
      }

      return stripped;
    })
    .join('\n');
}

/** Only the halves of a line that are identifiers or comments. */
function inspectableParts(line) {
  const comment = line.match(/(?:\/\/|#|\/\*|\*)\s?(.*)$/);
  const code = line.replace(/(?:\/\/|#|\/\*|\*).*$/, '');

  return [code, comment ? comment[1] : ''];
}

function findingsIn(source, path, kind) {
  const code = kind === 'markup' ? markupOnly(source) : kind === 'jsx' ? withoutJsxText(source) : source;
  const lines = withoutStringLiterals(code).split(/\r?\n/);
  const findings = [];

  lines.forEach((line, index) => {
    for (const part of inspectableParts(line)) {
      if (part.trim() === '') {
        continue;
      }

      const word = part.match(SPANISH_PATTERN);

      if (word) {
        findings.push({ path, line: index + 1, reason: `Spanish word "${word[1]}"` });
        return;
      }

      if (SPANISH_LETTERS.test(part)) {
        findings.push({ path, line: index + 1, reason: 'accented letter outside a string' });
        return;
      }
    }
  });

  return findings;
}

async function codeFilesIn(directory) {
  let entries;

  try {
    // A single file is a valid argument too, so one file can be checked
    // without checking the folder around it.
    if ((await stat(directory)).isFile()) {
      return CODE_EXTENSIONS.has(extname(directory)) ? [directory] : [];
    }

    entries = await readdir(directory, { withFileTypes: true });
  } catch {
    // A folder that does not exist yet is not a failure: the guard runs from
    // the first day, when most of them are still empty.
    return [];
  }

  const files = [];

  for (const entry of entries) {
    const path = join(directory, entry.name);

    if (entry.isDirectory()) {
      if (SKIPPED_DIRECTORIES.has(entry.name)) {
        continue;
      }

      files.push(...(await codeFilesIn(path)));
      continue;
    }

    if (CODE_EXTENSIONS.has(extname(entry.name))) {
      files.push(path);
    }
  }

  return files;
}

/** Markup, JSX, or plain code: the three ways text reaches a person. */
function kindOf(path) {
  const extension = extname(path);

  if (extension === '.html') {
    return 'markup';
  }

  return extension === '.jsx' || extension === '.tsx' ? 'jsx' : 'code';
}

async function main() {
  const roots = process.argv.slice(2).length > 0 ? process.argv.slice(2) : DEFAULT_ROOTS;
  const findings = [];
  let inspected = 0;

  for (const root of roots) {
    for (const path of await codeFilesIn(root)) {
      if (path.endsWith(SELF)) {
        continue;
      }

      inspected += 1;
      findings.push(...findingsIn(
        await readFile(path, 'utf8'),
        relative('.', path),
        kindOf(path),
      ));
    }
  }

  if (findings.length === 0) {
    console.log(`Language check passed: ${inspected} files, no Spanish in identifiers or comments.`);
    return 0;
  }

  console.error(`Language check failed: ${findings.length} findings in ${inspected} files.\n`);

  for (const finding of findings) {
    console.error(`  ${finding.path}:${finding.line}  ${finding.reason}`);
  }

  console.error('\nIdentifiers and comments are written in English (article XI).');
  console.error('Messages the user reads stay in Spanish, inside string literals.');

  return 1;
}

process.exit(await main());
