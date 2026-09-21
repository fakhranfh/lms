@section('title', $this->isEditing() ? 'Edit Teacher' : 'New Teacher')

<x-users.form
    entity="teacher"
    permission-prefix="teachers"
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
