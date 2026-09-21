@section('title', $this->isEditing() ? 'Edit Student' : 'New Student')

<x-users.form
    entity="student"
    permission-prefix="students"
    :user="$user"
    :name="$name"
    :email="$email"
    :password="$password"
    :password-confirmation="$password_confirmation"
    :photo="$photo"
    :photo-src="$this->savedPhotoSrc()"
    :login-link-ttl-days="$loginLinkTtlDays"
    :login-url="$loginUrl"
/>
