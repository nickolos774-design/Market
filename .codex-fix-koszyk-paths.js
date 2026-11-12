const fs = require('fs');

const base = 'Konto/Koszyk/Php_files/';
const files = [
  'Koszyk.php',
  'Dostawa.php',
  'Sposób płatności.php',
  'Podsumowanie.php',
  'Potwierdzenie.php',
  'dodaj_produkty.php',
  'zamowienie_do_tablicy.php',
];

const replacements = [
  ["__DIR__ . '/../../secure/config.php'", "__DIR__ . '/../../../secure/config.php'"],
  ['href="styleindex.css"', 'href="../Css_files/styleindex.css"'],
  ['href="../../Koszyk/Css_files/styleindex.css"', 'href="../Css_files/styleindex.css"'],
  ['../Koszyk/Zdjecia/', '../Zdjecia/'],
  ['../../Index/', '../../../Index/'],
  ['../../Kategoria/', '../../../Kategoria/'],
  ['../../StronaTowara/', '../../../StronaTowara/'],
  ['../../Regestracja and login/', '../../../Regestracja and login/'],
  ['../../FOTO/', '../../../FOTO/'],
  ['../../Konto/Schowek/', '../../Schowek/'],
  ['../../Konto/Zamowienia/', '../../Zamowienia/'],
  ['../../Konto/Opinie/', '../../Opinie/'],
  ['../../Konto/Faktura/', '../../Faktura/'],
  ['../../Konto/Moje Dane/', '../../Moje dane/'],
  ['../../Konto/Moje dane/', '../../Moje dane/'],
  ['../../Konto/Koszyk/Koszyk.php', 'Koszyk.php'],
  ['../../Konto/Koszyk/Php_files/Koszyk.php', 'Koszyk.php'],
  ['../Koszyk/Koszyk.php', 'Koszyk.php'],
  ['../Koszyk/Php_files/cart_count2.php', 'cart_count2.php'],
  ['../Opinie/Php_files/Opinie.php', '../../Opinie/Php_files/Opinie.php'],
  ['../Pracownik/', '../../../Pracownik/'],
  ['../Konto/Moje Dane/', '../../Moje dane/'],
  ['../Konto/Moje dane/', '../../Moje dane/'],
];

for (const file of files) {
  const path = base + file;
  let content = fs.readFileSync(path, 'utf8');

  for (const [from, to] of replacements) {
    content = content.split(from).join(to);
  }

  content = content
    .replace(/return '\.\.\/\.\.\/FOTO\/' \. ltrim\(\$matches\[1\], '\/'\);/g, "return '../../../FOTO/' . ltrim($matches[1], '/');")
    .replace(/return '\.\.\/' \. ltrim\(\$path, '\/'\);/g, "return '../../../' . ltrim($path, '/');");

  fs.writeFileSync(path, content, 'utf8');
}
