<x-mail::message>
# お問い合わせを受信しました

**お名前**
{{ $contact->name }}

**メールアドレス**
{{ $contact->email }}

**件名**
{{ $contact->subject }}

**お問い合わせ内容**
{!! nl2br(e($contact->body)) !!}

@if ($contact->user_id)
**ユーザーID**
{{ $contact->user_id }}（ログイン済みユーザー）
@else
未ログインユーザーからのお問い合わせです。
@endif

**受信日時**
{{ $contact->created_at->format('Y年m月d日 H:i') }}
</x-mail::message>