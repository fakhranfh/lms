@include('errors._page', [
    'code' => 429,
    'title' => 'Too Many Requests',
    'message' => "You've made too many requests. Please wait a moment and try again.",
])
