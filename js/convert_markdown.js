// Mini-interpréteur markdown convertissant vers HTML
function convert_md(text) {
  let res = text;
  let regex_replace = [
    // Image
    [/!\[([^\]]+)\]\(([^\)]+)\)/g, "<img alt='$1' src='$2'>"],
    // Lien web
    [/\[([^\]]+)\]\(([^\)]+)\)/g, "<a href='$2' target='_blank'>$1</a>"],
    // Mettre en gras
    [/\*\*([^\*]+)\*\*/g, "<strong>$1</strong>"],
    // Ajouter du code
    [/```([^`]+)```/g, "<pre><code>$1</code></pre>"]
  ];
  regex_replace.forEach(function([regex, replace]) {
    res = res.replace(regex, replace);
  });
  return res;
}