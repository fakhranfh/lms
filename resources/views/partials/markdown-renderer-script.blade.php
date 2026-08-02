<script>
    if (! window.renderMarkdown) {
        window.renderMarkdown = function(markdown) {
            let html = escapeHtml(markdown);

            // Code blocks FIRST (before other replacements)
            html = html.replace(/```[\s\S]*?```/g, function (match) {
                const code = match.slice(3, -3).trim();

                return '\n<pre class="bg-surface-container p-space-lg rounded overflow-x-auto my-space-lg"><code class="font-mono text-body-sm text-on-surface">' +
                    code + '</code></pre>\n';
            });

            // Inline code (must be before other formatting)
            html = html.replace(/`([^`\n]+)`/g, '<code class="bg-surface-container px-space-xs py-space-xxs rounded font-mono text-body-sm text-on-surface">$1</code>');

            // Headers
            html = html.replace(/^### (.*?)$/gm, '<h3 class="text-headline-sm font-headline-sm text-on-surface mt-space-lg mb-space-md">$1</h3>');
            html = html.replace(/^## (.*?)$/gm, '<h2 class="text-headline-md font-headline-md text-on-surface mt-space-lg mb-space-md">$1</h2>');
            html = html.replace(/^# (.*?)$/gm, '<h1 class="text-headline-lg font-headline-lg text-on-surface mt-space-lg mb-space-md">$1</h1>');

            // Bold (must be before italic)
            html = html.replace(/\*\*(.*?)\*\*/g, '<strong class="font-semibold text-on-surface">$1</strong>');
            html = html.replace(/__(.+?)__/g, '<strong class="font-semibold text-on-surface">$1</strong>');

            // Italic
            html = html.replace(/\*([^*\n]+)\*/g, '<em class="italic text-on-surface">$1</em>');
            html = html.replace(/_([^_\n]+)_/g, '<em class="italic text-on-surface">$1</em>');

            // Links
            html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" class="text-primary hover:underline transition" target="_blank">$1</a>');

            // Blockquotes
            html = html.replace(/^&gt; (.*?)$/gm, '<blockquote class="border-l-4 border-primary pl-space-md italic text-on-surface-variant my-space-md">$1</blockquote>');

            // Unordered lists
            html = html.replace(/^- (.*?)$/gm, '<li class="ml-space-md text-on-surface">$1</li>');
            html = html.replace(/(<li[^>]*>.*?<\/li>)/s, '<ul class="list-disc space-y-space-xs mb-space-md">$1</ul>');

            // Paragraphs
            html = html.split('\n\n').map(function (para) {
                para = para.trim();
                if (! para) return '';
                if (para.startsWith('<h') || para.startsWith('<pre') ||
                    para.startsWith('<ul') || para.startsWith('<blockquote')) {
                    return para;
                }

                return '<p class="mb-space-md text-on-surface">' + para + '</p>';
            }).join('');

            return html;
        };

        var escapeHtml = function (text) {
            const div = document.createElement('div');
            div.textContent = text;

            return div.innerHTML;
        };
    }
</script>
