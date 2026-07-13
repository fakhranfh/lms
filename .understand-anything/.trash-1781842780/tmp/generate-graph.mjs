import { readFileSync, writeFileSync } from 'fs';

const scan = JSON.parse(readFileSync('d:/Projek/laravel-13-boilerplate/.understand-anything/intermediate/scan-result.json','utf8'));
const im = scan.importMap;

const allResults = [];
for (let i = 1; i <= 7; i++) {
  const r = JSON.parse(readFileSync('d:/Projek/laravel-13-boilerplate/.understand-anything/tmp/ua-file-extract-results-' + i + '.json','utf8'));
  allResults.push(...r.results);
}

const batches = JSON.parse(readFileSync('d:/Projek/laravel-13-boilerplate/.understand-anything/intermediate/batches.json','utf8'));

function nodeType(cat) {
  const map = { code:'file', config:'config', docs:'document', infra:'service', data:'table', script:'file', markup:'file' };
  return map[cat] || 'file';
}

function inferTags(path, cat) {
  const tags = [];
  if (path.startsWith('app/Actions/')) { tags.push('action','fortify'); }
  if (path.startsWith('app/Http/Controllers/')) tags.push('controller');
  if (path.startsWith('app/Http/Middleware/')) tags.push('middleware');
  if (path.startsWith('app/Http/Responses/')) tags.push('response');
  if (path.startsWith('app/Models/')) { tags.push('model','eloquent'); }
  if (path.startsWith('app/Providers/')) tags.push('service-provider');
  if (path.startsWith('config/')) tags.push('configuration');
  if (path.startsWith('database/migrations/')) { tags.push('migration','database'); }
  if (path.startsWith('database/factories/')) { tags.push('factory','testing'); }
  if (path.startsWith('database/seeders/')) { tags.push('seeder','database'); }
  if (path.startsWith('routes/')) tags.push('routing');
  if (path.startsWith('resources/views/auth/')) { tags.push('authentication','blade','view'); }
  if (path.startsWith('resources/views/') && !path.includes('/auth/')) { tags.push('blade','view'); }
  if (path.startsWith('resources/css/')) { tags.push('stylesheet','tailwind'); }
  if (path.startsWith('resources/js/')) tags.push('javascript');
  if (path.startsWith('tests/')) { tags.push('test','pest'); }
  if (path.startsWith('bootstrap/')) { tags.push('bootstrap','entry-point'); }
  if (path === 'public/index.php') tags.push('entry-point');
  if (path.includes('Fortify')) { tags.push('fortify','authentication'); }
  if (path.includes('Password')) { tags.push('authentication','password'); }
  if (path.includes('TwoFactor') || path.includes('two_factor')) { tags.push('authentication','2fa'); }
  if (path.includes('passkey')) { tags.push('authentication','passkey'); }
  if (path.endsWith('.blade.php')) { tags.push('blade','template'); }
  if (cat === 'docs') tags.push('documentation');
  if (path === 'vite.config.js') { tags.push('build-tool','vite'); }
  if (path.startsWith('.github/workflows/')) { tags.push('ci-cd','deployment'); }
  return [...new Set(tags)].slice(0, 5);
}

function inferSummary(path, cat, sizeLines) {
  const map = {
    'app/Models/User.php': 'Core User model with Fortify two-factor authentication and passkey support.',
    'app/Providers/FortifyServiceProvider.php': 'Registers Fortify authentication actions and custom response bindings.',
    'app/Providers/AppServiceProvider.php': 'Application service provider bootstrapping app-level services.',
    'app/Actions/Fortify/CreateNewUser.php': 'Handles new user registration with validation and creation logic.',
    'app/Actions/Fortify/UpdateUserPassword.php': 'Updates the authenticated user password with current-password verification.',
    'app/Actions/Fortify/UpdateUserProfileInformation.php': 'Updates authenticated user profile information with unique email validation.',
    'app/Actions/Fortify/ResetUserPassword.php': 'Resets a user password using Fortify password reset flow.',
    'app/Actions/Fortify/PasswordValidationRules.php': 'Provides default Laravel password validation rules.',
    'app/Http/Middleware/EnsureTokenIsValid.php': 'Middleware that validates API tokens for route protection.',
    'app/Http/Controllers/Controller.php': 'Base controller class extended by all application controllers.',
    'app/Http/Responses/CustomPasswordResetLinkResponse.php': 'Custom Fortify response for password reset link requests.',
    'app/Http/Responses/CustomPasswordResetResponse.php': 'Custom Fortify response after successful password reset.',
    'app/Http/Responses/CustomVerifyEmailViewResponse.php': 'Custom Fortify response for email verification view handling.',
    'config/fortify.php': 'Laravel Fortify configuration: auth features, password rules, and verification settings.',
    'config/auth.php': 'Authentication guard and provider configuration with Fortify defaults.',
    'config/app.php': 'Core application configuration: name, env, providers, and aliases.',
    'config/database.php': 'Database connection configuration for multiple environments.',
    'config/session.php': 'Session driver and lifetime configuration.',
    'config/cache.php': 'Cache store configuration with Redis and file driver settings.',
    'config/filesystems.php': 'Filesystem disk configuration for local, S3, and other storage drivers.',
    'config/logging.php': 'Logging channel configuration with stack and daily driver settings.',
    'config/mail.php': 'Mail driver configuration for SMTP, Mailgun, and other transports.',
    'config/queue.php': 'Queue connection configuration for database, Redis, and SQS drivers.',
    'config/services.php': 'Third-party service integrations configuration.',
    'routes/web.php': 'Web route definitions with Fortify auth routes and dashboard.',
    'routes/console.php': 'Console route definitions for Artisan closure commands.',
    'public/index.php': 'Laravel application entry point that bootstraps the HTTP kernel.',
    'bootstrap/app.php': 'Laravel application bootstrap configuring routing, middleware, and exceptions.',
    'bootstrap/providers.php': 'Service provider manifest for optimized provider loading.',
    'database/factories/UserFactory.php': 'Model factory generating test User instances with hashed passwords.',
    'database/seeders/DatabaseSeeder.php': 'Primary database seeder coordinating all seed operations.',
    'resources/views/auth/login.blade.php': 'Login page with email/password form and forgot-password link.',
    'resources/views/auth/register.blade.php': 'Registration page with name, email, password, and confirmation fields.',
    'resources/views/auth/forgot-password.blade.php': 'Password reset request form for email-based recovery.',
    'resources/views/auth/reset-password.blade.php': 'New password form shown after clicking reset link from email.',
    'resources/views/auth/verify-email.blade.php': 'Email verification prompt with resend link.',
    'resources/views/auth/master.blade.php': 'Auth layout master template with head, body, and guest layout structure.',
    'resources/views/auth/success-and-error-alert.blade.php': 'Reusable alert component for auth form success and error feedback.',
    'resources/views/master.blade.php': 'Main application layout with HTML structure, meta tags, and Vite assets.',
    'resources/views/dashboard.blade.php': 'Authenticated user dashboard displaying user information.',
    'resources/views/landing-page.blade.php': 'Public landing page shown to unauthenticated visitors.',
    'resources/css/app.css': 'Application Tailwind CSS entry point.',
    'resources/js/app.js': 'Application JavaScript entry point with Vite imports.',
    'vite.config.js': 'Vite build configuration with Laravel and Tailwind CSS plugins.',
    'tests/Pest.php': 'Pest test configuration with global setup and helper functions.',
    'tests/TestCase.php': 'Base test case class extending Laravel testing utilities.',
    'tests/Feature/ExampleTest.php': 'Example feature test verifying application returns successful response.',
    'tests/Unit/ExampleTest.php': 'Example unit test placeholder.',
    'composer.json': 'PHP dependency manifest defining Laravel, Fortify, and dev packages.',
    'package.json': 'Node.js dependency manifest with Vite, Tailwind CSS, and build tools.',
    'README.md': 'Project overview: Laravel 13 Boilerplate personal finance tracker via WhatsApp.',
    'AGENTS.md': 'Agent guidelines for Laravel Boost with coding conventions and tool instructions.',
    'phpunit.xml': 'PHPUnit configuration with test suite definitions and environment settings.',
  };
  if (map[path]) return map[path];
  if (path.startsWith('database/migrations/')) {
    if (path.includes('_create_users_table')) return 'Creates the users table with name, email, and password columns.';
    if (path.includes('_create_cache_table')) return 'Creates the cache table for database cache driver.';
    if (path.includes('_create_jobs_table')) return 'Creates the jobs table for database queue driver.';
    if (path.includes('two_factor')) return 'Adds two-factor authentication columns to users table.';
    if (path.includes('passkeys')) return 'Creates the passkeys table for WebAuthn passkey authentication.';
    return 'Database migration file.';
  }
  if (path.startsWith('.github/workflows/')) return 'GitHub Actions CI workflow for automated testing and deployment.';
  return cat === 'code' ? 'PHP source file (' + sizeLines + ' lines).' : cat + ' file (' + sizeLines + ' lines).';
}

function complexity(sizeLines) {
  if (sizeLines < 50) return 'simple';
  if (sizeLines < 200) return 'moderate';
  return 'complex';
}

const nodes = [];
const edges = [];
const nodeSet = new Set();

for (const f of scan.files) {
  const type = nodeType(f.fileCategory);
  const id = type + ':' + f.path;
  const summary = inferSummary(f.path, f.fileCategory, f.sizeLines);
  const tags = inferTags(f.path, f.fileCategory);
  const compl = complexity(f.sizeLines);
  nodes.push({ id, type, name: f.path.split('/').pop(), filePath: f.path, summary, tags, complexity: compl });
  nodeSet.add(id);
}

for (const r of allResults) {
  const fileId = 'file:' + r.path;
  for (const fn of (r.functions || [])) {
    if (fn.name.startsWith('{') || fn.name.startsWith('lambda')) continue;
    const lines = fn.endLine - fn.startLine + 1;
    const isExported = (r.exports || []).some(e => e.name === fn.name);
    if (!isExported && lines < 10) continue;
    const id = 'function:' + r.path + ':' + fn.name;
    const fnTags = ['method'];
    if (isExported) fnTags.push('public');
    nodes.push({ id, type: 'function', name: fn.name, filePath: r.path, summary: fn.name + ' method', tags: fnTags, complexity: lines < 20 ? 'simple' : 'moderate' });
    nodeSet.add(id);
    edges.push({ source: fileId, target: id, type: 'contains', direction: 'forward', weight: 1.0 });
  }
  for (const cls of (r.classes || [])) {
    const id = 'class:' + r.path + ':' + cls.name;
    const clsTags = ['class'];
    if ((r.exports || []).some(e => e.name === cls.name)) clsTags.push('exported');
    nodes.push({ id, type: 'class', name: cls.name, filePath: r.path, summary: cls.name + ' class', tags: clsTags, complexity: 'moderate' });
    nodeSet.add(id);
    edges.push({ source: fileId, target: id, type: 'contains', direction: 'forward', weight: 1.0 });
  }
}

for (const [src, imports] of Object.entries(im)) {
  if (!Array.isArray(imports)) continue;
  const srcId = 'file:' + src;
  if (!nodeSet.has(srcId)) continue;
  for (const target of imports) {
    const targetId = 'file:' + target;
    if (nodeSet.has(targetId)) {
      edges.push({ source: srcId, target: targetId, type: 'imports', direction: 'forward', weight: 0.7 });
    }
  }
}

for (const f of scan.files) {
  if (!f.path.startsWith('tests/')) continue;
  const testId = 'file:' + f.path;
  if (!nodeSet.has(testId)) continue;
  const testImports = im[f.path] || [];
  for (const target of testImports) {
    const targetId = 'file:' + target;
    if (nodeSet.has(targetId)) {
      edges.push({ source: targetId, target: testId, type: 'tested_by', direction: 'forward', weight: 0.5 });
    }
  }
}

for (const f of scan.files) {
  if (f.fileCategory !== 'config' && !f.path.startsWith('config/') && f.path !== '.env.example') continue;
  const cfgId = (f.fileCategory === 'config' ? 'config:' : 'file:') + f.path;
  if (!nodeSet.has(cfgId)) continue;
  if (f.path === 'config/fortify.php') {
    for (const af of scan.files) {
      if (af.path.startsWith('app/Actions/Fortify/') || af.path.startsWith('app/Http/Responses/')) {
        edges.push({ source: cfgId, target: 'file:' + af.path, type: 'configures', direction: 'forward', weight: 0.6 });
      }
    }
  }
  if (f.path === 'config/auth.php') {
    edges.push({ source: cfgId, target: 'file:app/Models/User.php', type: 'configures', direction: 'forward', weight: 0.6 });
  }
}

for (const f of scan.files) {
  if (f.fileCategory !== 'docs') continue;
  const docId = 'document:' + f.path;
  if (!nodeSet.has(docId)) continue;
  if (f.path === 'README.md' || f.path === 'AGENTS.md') {
    edges.push({ source: docId, target: 'file:public/index.php', type: 'documents', direction: 'forward', weight: 0.5 });
    edges.push({ source: docId, target: 'file:app/Providers/FortifyServiceProvider.php', type: 'documents', direction: 'forward', weight: 0.5 });
  }
}

for (const f of scan.files) {
  if (!f.path.startsWith('database/migrations/')) continue;
  const migId = 'file:' + f.path;
  if (f.path.includes('_create_users_table') || f.path.includes('two_factor') || f.path.includes('passkeys')) {
    edges.push({ source: migId, target: 'file:app/Models/User.php', type: 'migrates', direction: 'forward', weight: 0.7 });
  }
}

const workflowFile = scan.files.find(f => f.path.startsWith('.github/workflows/'));
if (workflowFile) {
  edges.push({ source: 'file:' + workflowFile.path, target: 'file:public/index.php', type: 'deploys', direction: 'forward', weight: 0.7 });
}

function findBatch(fp) {
  for (const b of batches.batches) {
    if (b.files.some(f => f.path === fp)) return b.batchIndex;
  }
  return null;
}

const batchNodes = {};
for (let i = 1; i <= 7; i++) batchNodes[i] = { nodes: [], edges: [] };

for (const n of nodes) {
  const bi = findBatch(n.filePath);
  if (bi) batchNodes[bi].nodes.push(n);
}

for (const e of edges) {
  const srcFp = e.source.split(':').slice(1).join(':');
  const bi = findBatch(srcFp);
  if (bi) batchNodes[bi].edges.push(e);
}

for (let i = 1; i <= 7; i++) {
  writeFileSync('d:/Projek/laravel-13-boilerplate/.understand-anything/intermediate/batch-' + i + '.json', JSON.stringify(batchNodes[i], null, 2));
}

console.log('Total nodes:', nodes.length);
console.log('Total edges:', edges.length);
console.log('Node types:', JSON.stringify(nodes.reduce((a,n) => { a[n.type]=(a[n.type]||0)+1; return a; }, {})));
console.log('Edge types:', JSON.stringify(edges.reduce((a,e) => { a[e.type]=(a[e.type]||0)+1; return a; }, {})));
