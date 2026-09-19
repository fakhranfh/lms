@section('title', 'Students')

<x-users.index
    entity="student"
    permission-prefix="students"
    :items="$students"
    :loaded="$studentsLoaded"
    :sort="$sort"
    :direction="$direction"
    :search="$search"
    :success-message="$successMessage"
    :error-message="$errorMessage"
    :regenerated-login-url="$regeneratedLoginUrl"
    generate-component="students.student-generate"
/>
