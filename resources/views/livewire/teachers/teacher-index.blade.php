@section('title', 'Teachers')

<x-users.index
    entity="teacher"
    permission-prefix="teachers"
    :items="$teachers"
    :loaded="$teachersLoaded"
    :sort="$sort"
    :direction="$direction"
    :search="$search"
    :success-message="$successMessage"
    :error-message="$errorMessage"
    :regenerated-login-url="$regeneratedLoginUrl"
    generate-component="teachers.teacher-generate"
/>
