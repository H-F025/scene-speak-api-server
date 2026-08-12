<x-mail::message>
# お問い合わせを受信しました

**お名前**
{{ $contact->name }}

**メールアドレス**
{{ $contact->email }}

**お問い合わせ内容**
{!! nl2br(e($contact->message)) !!}

**ユーザーID**
{{ $contact->user_id }}

**受信日時**
{{ $contact->created_at->format('Y年m月d日 H:i') }}
</x-mail::message>