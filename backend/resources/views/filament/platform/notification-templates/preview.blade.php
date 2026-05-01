<div class="space-y-6">
    <div class="space-y-2">
        <div class="text-sm text-gray-500 dark:text-gray-400">Event</div>
        <div class="font-mono text-sm">{{ $event }}</div>
    </div>

    <div class="space-y-2">
        <div class="text-sm text-gray-500 dark:text-gray-400">Rendered title</div>
        <div class="rounded-lg border border-gray-200 bg-white p-3 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-900">
            {{ $renderedTitle }}
        </div>
    </div>

    <div class="space-y-2">
        <div class="text-sm text-gray-500 dark:text-gray-400">Rendered body</div>
        <div class="rounded-lg border border-gray-200 bg-white p-3 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-900">
            {{ $renderedBody }}
        </div>
    </div>

    <div class="space-y-2">
        <div class="text-sm text-gray-500 dark:text-gray-400">Sample payload</div>
        <pre class="rounded-lg border border-gray-200 bg-white p-3 text-xs shadow-sm dark:border-gray-700 dark:bg-gray-900">{{ json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </div>
</div>

