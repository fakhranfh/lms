import { readFileSync, writeFileSync } from 'fs';

const graph = JSON.parse(readFileSync('d:/Projek/laravel-13-boilerplate/.understand-anything/intermediate/assembled-graph.json','utf8'));

// Remove temp file nodes
const tmpPrefixes = ['.understand-anything/tmp/', '.understand-anything/intermediate/scan-result.json'];
graph.nodes = graph.nodes.filter(n => {
  const fp = n.filePath || '';
  return !tmpPrefixes.some(p => fp.startsWith(p));
});

// Remove edges referencing removed nodes
const validIds = new Set(graph.nodes.map(n => n.id));
graph.edges = graph.edges.filter(e => validIds.has(e.source) && validIds.has(e.target));

// Fix missing tags
const tagMap = {
  'config:.env.example': ['configuration', 'environment'],
  'config:boost.json': ['configuration', 'laravel-boost'],
  'config:composer.json': ['configuration', 'dependency-management'],
  'config:opencode.json': ['configuration'],
  'config:package.json': ['configuration', 'dependency-management'],
  'config:phpunit.xml': ['configuration', 'testing'],
  'file:.gitattributes': ['configuration', 'git'],
  'file:.husky/commit-msg': ['git-hooks', 'commitlint'],
  'file:.npmrc': ['configuration', 'npm'],
  'file:.understand-anything/.understandignore': ['configuration'],
  'file:artisan': ['entry-point', 'cli'],
  'file:commitlint.config.js': ['configuration', 'commitlint'],
  'file:public/.htaccess': ['configuration', 'apache', 'security'],
};

for (const n of graph.nodes) {
  if (tagMap[n.id] && (!n.tags || n.tags.length === 0)) {
    n.tags = tagMap[n.id];
  }
  if (!n.tags || n.tags.length === 0) {
    n.tags = ['untagged'];
  }
}

writeFileSync('d:/Projek/laravel-13-boilerplate/.understand-anything/intermediate/assembled-graph.json', JSON.stringify(graph, null, 2));
console.log('Fixed graph. Nodes:', graph.nodes.length, 'Edges:', graph.edges.length);
