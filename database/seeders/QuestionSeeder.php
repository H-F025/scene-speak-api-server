<?php

namespace Database\Seeders;

use App\Models\EnglishLevel;
use App\Models\Question;
use App\Models\Theme;
use App\Models\ThemeLevel;
use Illuminate\Database\Seeder;
class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        // 全テーマと全英語レベルを取得する
        $themes = Theme::orderBy('sort_order', 'asc')->get();
        $englishLevels = EnglishLevel::orderBy('sort_order', 'asc')->get();
        // テーマと英語レベルの組み合わせ分だけ問題を登録する
        foreach ($themes as $theme) {
            foreach ($englishLevels as $englishLevel) {
                // テーマと英語レベルに対応するテーマレベルを取得する
                $themeLevel = ThemeLevel::where('theme_id', $theme->id)
                    ->where('english_level_id', $englishLevel->id)
                    ->first();
                // テーマ名と英語レベルコードに対応する問題データを取得する
                $questions = $this->getQuestions($theme->name, $englishLevel->code);
                // 問題データをDBに登録する（既存データがあれば更新する）
                foreach ($questions as $questionData) {
                    Question::updateOrCreate(
                        [
                            'theme_level_id' => $themeLevel->id,
                            'number' => $questionData['number'],
                        ],
                        [
                            'title' => $questionData['title'],
                            'scene_label' => $questionData['scene_label'],
                            'partner_message' => $questionData['partner_message'],
                            'instruction' => '次の日本語を英語にしましょう',
                            'question' => $questionData['question'],
                            'hint' => $questionData['hint'],
                            'correct_explanation' => $questionData['correct_explanation'],
                            'incorrect_explanation' => $questionData['incorrect_explanation'],
                            'sort_order' => $questionData['number'],
                        ]
                    );
                }
            }
        }
    }
    // テーマ名に応じて、対応する問題取得メソッドに振り分ける
    private function getQuestions($themeName, $englishLevelCode)
    {
        if ($themeName === 'カフェで注文') {
            return $this->getCafeQuestions($englishLevelCode);
        }
        if ($themeName === '空港でチェックイン') {
            return $this->getAirportQuestions($englishLevelCode);
        }
        if ($themeName === 'ホテルで質問') {
            return $this->getHotelQuestions($englishLevelCode);
        }
        if ($themeName === '自己紹介') {
            return $this->getIntroductionQuestions($englishLevelCode);
        }
        if ($themeName === '仕事の打ち合わせ') {
            return $this->getMeetingQuestions($englishLevelCode);
        }
        if ($themeName === 'フリートーク') {
            return $this->getFreeTalkQuestions($englishLevelCode);
        }
    }
    // カフェで注文テーマの問題を英語レベルごとに返す
    private function getCafeQuestions($englishLevelCode)
    {
        if ($englishLevelCode === 'beginner') {
            return [
                [
                    'number' => 1,
                    'title' => '注文する',
                    'scene_label' => '店員さん',
                    'partner_message' => 'What can I get for you today?',
                    'question' => 'コーヒーを一つください。',
                    'hint' => 'Could I get 〜, please? を使うと丁寧です。',
                    'correct_choice' => 'Could I get a coffee, please?',
                    'wrong_choice_1' => 'I want a coffee.',
                    'wrong_choice_2' => 'Give me a coffee.',
                    'wrong_choice_3' => 'A coffee, please. One cup.',
                    'correct_explanation' => 'Could I get 〜, please? はカフェで丁寧に注文するときによく使います。',
                    'incorrect_explanation' => 'I want 〜 や Give me 〜 は少し直接的です。Could I get 〜, please? を使うと自然です。',
                ],
                [
                    'number' => 2,
                    'title' => '挨拶する',
                    'scene_label' => '店員さん',
                    'partner_message' => 'Hi, how are you?',
                    'question' => 'こんにちは。元気です。',
                    'hint' => '短く Hi, I’m good. と返せます。',
                    'correct_choice' => 'Hi, I’m good, thank you.',
                    'wrong_choice_1' => 'I am coffee.',
                    'wrong_choice_2' => 'Yes, hello good.',
                    'wrong_choice_3' => 'No, I am fine coffee.',
                    'correct_explanation' => 'Hi, I’m good, thank you. は自然な挨拶の返し方です。',
                    'incorrect_explanation' => '挨拶では、まず Hi と返し、その後に I’m good などで状態を伝えると自然です。',
                ],
                [
                    'number' => 3,
                    'title' => 'メニューを見る',
                    'scene_label' => '店員さん',
                    'partner_message' => 'Are you ready to order?',
                    'question' => 'メニューを見てもいいですか？',
                    'hint' => 'Can I see 〜? を使います。',
                    'correct_choice' => 'Can I see the menu?',
                    'wrong_choice_1' => 'I see menu.',
                    'wrong_choice_2' => 'Menu look please.',
                    'wrong_choice_3' => 'Give menu now.',
                    'correct_explanation' => 'Can I see the menu? は「メニューを見てもいいですか？」という自然な表現です。',
                    'incorrect_explanation' => 'menu だけでは文になりません。Can I see the menu? の形で言うと自然です。',
                ],
                [
                    'number' => 4,
                    'title' => '確認する',
                    'scene_label' => '店員さん',
                    'partner_message' => 'Anything else?',
                    'question' => 'それだけです。',
                    'hint' => 'That’s all. を使います。',
                    'correct_choice' => 'That’s all, thank you.',
                    'wrong_choice_1' => 'It is only.',
                    'wrong_choice_2' => 'Only one finish.',
                    'wrong_choice_3' => 'No more coffee yes.',
                    'correct_explanation' => 'That’s all, thank you. は注文を終えるときに使いやすい表現です。',
                    'incorrect_explanation' => '「それだけです」は That’s all. が自然です。最後に thank you を付けると丁寧です。',
                ],
                [
                    'number' => 5,
                    'title' => 'お礼を言う',
                    'scene_label' => '店員さん',
                    'partner_message' => 'Here you go.',
                    'question' => 'ありがとうございます。',
                    'hint' => 'Thank you. で大丈夫です。',
                    'correct_choice' => 'Thank you.',
                    'wrong_choice_1' => 'Please you.',
                    'wrong_choice_2' => 'Thanks coffee.',
                    'wrong_choice_3' => 'I thank it.',
                    'correct_explanation' => 'Thank you. は最も基本的なお礼の表現です。',
                    'incorrect_explanation' => 'お礼は Thank you. または Thanks. と言うと自然です。',
                ],
                [
                    'number' => 6,
                    'title' => '支払いを頼む',
                    'scene_label' => '店員さん',
                    'partner_message' => 'Is that for here?',
                    'question' => '会計をお願いします。',
                    'hint' => 'check は会計の意味で使えます。',
                    'correct_choice' => 'Can I have the check, please?',
                    'wrong_choice_1' => 'I want money.',
                    'wrong_choice_2' => 'Please payment me.',
                    'wrong_choice_3' => 'Give check.',
                    'correct_explanation' => 'Can I have the check, please? は会計をお願いするときに使えます。',
                    'incorrect_explanation' => 'Give check. は直接的です。Can I have 〜, please? を使うと丁寧です。',
                ],
                [
                    'number' => 7,
                    'title' => 'おかわりを頼む',
                    'scene_label' => '店員さん',
                    'partner_message' => 'Would you like anything else?',
                    'question' => 'もう一杯コーヒーをください。',
                    'hint' => 'another を使うと「もう一つ」を表せます。',
                    'correct_choice' => 'Could I get another coffee, please?',
                    'wrong_choice_1' => 'One more coffee give.',
                    'wrong_choice_2' => 'I want coffee again now.',
                    'wrong_choice_3' => 'Coffee second please me.',
                    'correct_explanation' => 'another coffee で「もう一杯のコーヒー」という意味になります。',
                    'incorrect_explanation' => '「もう一杯」は another を使うと自然です。',
                ],
                [
                    'number' => 8,
                    'title' => '席を確認する',
                    'scene_label' => '店員さん',
                    'partner_message' => 'For here or to go?',
                    'question' => 'ここで飲みます。',
                    'hint' => 'For here. と短く言えます。',
                    'correct_choice' => 'For here, please.',
                    'wrong_choice_1' => 'Here drink me.',
                    'wrong_choice_2' => 'I am here coffee.',
                    'wrong_choice_3' => 'To go here.',
                    'correct_explanation' => 'For here, please. は店内利用を伝える自然な表現です。',
                    'incorrect_explanation' => '店内利用は For here.、持ち帰りは To go. と表現します。',
                ],
                [
                    'number' => 9,
                    'title' => '感想を伝える',
                    'scene_label' => '店員さん',
                    'partner_message' => 'How was your coffee?',
                    'question' => 'とてもおいしかったです。',
                    'hint' => 'It was 〜. を使います。',
                    'correct_choice' => 'It was very good.',
                    'wrong_choice_1' => 'It very good was.',
                    'wrong_choice_2' => 'Coffee good me.',
                    'wrong_choice_3' => 'I am delicious.',
                    'correct_explanation' => 'It was very good. は感想を伝える基本的な表現です。',
                    'incorrect_explanation' => '食べ物や飲み物が「おいしい」は It was good. で自然に伝えられます。',
                ],
                [
                    'number' => 10,
                    'title' => '別れを告げる',
                    'scene_label' => '店員さん',
                    'partner_message' => 'Have a nice day!',
                    'question' => 'ありがとうございます。あなたも良い一日を。',
                    'hint' => 'You too. を使うと自然です。',
                    'correct_choice' => 'Thank you. You too.',
                    'wrong_choice_1' => 'Yes, day good.',
                    'wrong_choice_2' => 'You nice me.',
                    'wrong_choice_3' => 'Thanks. I day.',
                    'correct_explanation' => 'Thank you. You too. は別れ際の自然な返し方です。',
                    'incorrect_explanation' => '相手の Have a nice day! には You too. と返すと自然です。',
                ],
            ];
        }
        if ($englishLevelCode === 'intermediate') {
            return $this->makeIntermediateQuestions('店員さん');
        }
        return $this->makeAdvancedQuestions('店員さん');
    }
    // 空港でチェックインテーマの問題を英語レベルごとに返す
    private function getAirportQuestions($englishLevelCode)
    {
        if ($englishLevelCode === 'beginner') {
            return $this->makeBeginnerQuestions('空港スタッフ');
        }
        if ($englishLevelCode === 'intermediate') {
            return $this->makeIntermediateQuestions('空港スタッフ');
        }
        return $this->makeAdvancedQuestions('空港スタッフ');
    }
    // ホテルで質問テーマの問題を英語レベルごとに返す
    private function getHotelQuestions($englishLevelCode)
    {
        if ($englishLevelCode === 'beginner') {
            return $this->makeBeginnerQuestions('ホテルスタッフ');
        }
        if ($englishLevelCode === 'intermediate') {
            return $this->makeIntermediateQuestions('ホテルスタッフ');
        }
        return $this->makeAdvancedQuestions('ホテルスタッフ');
    }
    // 自己紹介テーマの問題を英語レベルごとに返す
    private function getIntroductionQuestions($englishLevelCode)
    {
        if ($englishLevelCode === 'beginner') {
            return $this->makeBeginnerQuestions('相手');
        }
        if ($englishLevelCode === 'intermediate') {
            return $this->makeIntermediateQuestions('相手');
        }
        return $this->makeAdvancedQuestions('相手');
    }
    // 仕事の打ち合わせテーマの問題を英語レベルごとに返す
    private function getMeetingQuestions($englishLevelCode)
    {
        if ($englishLevelCode === 'beginner') {
            return $this->makeBeginnerQuestions('同僚');
        }
        if ($englishLevelCode === 'intermediate') {
            return $this->makeIntermediateQuestions('同僚');
        }
        return $this->makeAdvancedQuestions('同僚');
    }
    // フリートークテーマの問題を英語レベルごとに返す
    private function getFreeTalkQuestions($englishLevelCode)
    {
        if ($englishLevelCode === 'beginner') {
            return $this->makeBeginnerQuestions('相手');
        }
        if ($englishLevelCode === 'intermediate') {
            return $this->makeIntermediateQuestions('相手');
        }
        return $this->makeAdvancedQuestions('相手');
    }
    // 初級レベルの汎用問題データを返す（カフェ以外のテーマで使用）
    private function makeBeginnerQuestions($sceneLabel)
    {
        return [
            $this->makeQuestion(1, 'あいさつする', $sceneLabel, 'Hello!', 'こんにちは。', 'Hello. と返しましょう。', 'Hello.'),
            $this->makeQuestion(2, '名前を伝える', $sceneLabel, 'What is your name?', '私の名前はユウスケです。', 'My name is 〜. を使います。', 'My name is Yusuke.'),
            $this->makeQuestion(3, 'お願いする', $sceneLabel, 'How can I help you?', '手伝ってください。', 'Can you help me? を使います。', 'Can you help me?'),
            $this->makeQuestion(4, '確認する', $sceneLabel, 'Is that OK?', 'はい、大丈夫です。', 'That’s OK. を使います。', 'Yes, that’s OK.'),
            $this->makeQuestion(5, '質問する', $sceneLabel, 'Do you have any questions?', '質問があります。', 'I have a question. を使います。', 'I have a question.'),
            $this->makeQuestion(6, '時間を聞く', $sceneLabel, 'What time is good for you?', '何時ですか？', 'What time 〜? を使います。', 'What time is it?'),
            $this->makeQuestion(7, '場所を聞く', $sceneLabel, 'Where would you like to go?', '場所はどこですか？', 'Where is 〜? を使います。', 'Where is the place?'),
            $this->makeQuestion(8, 'もう一度聞く', $sceneLabel, 'Did you understand?', 'もう一度言ってください。', 'Could you say that again? を使います。', 'Could you say that again?'),
            $this->makeQuestion(9, 'お礼を言う', $sceneLabel, 'Here you are.', 'ありがとうございます。', 'Thank you. を使います。', 'Thank you.'),
            $this->makeQuestion(10, '別れを告げる', $sceneLabel, 'See you!', 'また会いましょう。', 'See you. を使います。', 'See you.'),
        ];
    }
    // 中級レベルの汎用問題データを返す
    private function makeIntermediateQuestions($sceneLabel)
    {
        return [
            $this->makeQuestion(1, '丁寧に依頼する', $sceneLabel, 'How can I help you?', '少し手伝っていただけますか？', 'Could you 〜? を使います。', 'Could you help me for a moment?'),
            $this->makeQuestion(2, '希望を伝える', $sceneLabel, 'What would you like?', 'できれば窓側がいいです。', 'I’d like 〜 if possible. を使います。', 'I’d like a window seat if possible.'),
            $this->makeQuestion(3, '理由を伝える', $sceneLabel, 'Why do you need that?', '急いでいるので、早めにお願いします。', 'because を使って理由を伝えます。', 'Could you do it soon because I’m in a hurry?'),
            $this->makeQuestion(4, '確認する', $sceneLabel, 'Is everything correct?', '内容を確認してもいいですか？', 'Can I check 〜? を使います。', 'Can I check the details?'),
            $this->makeQuestion(5, '提案する', $sceneLabel, 'What should we do?', 'この方法を試してみませんか？', 'Why don’t we 〜? を使います。', 'Why don’t we try this way?'),
            $this->makeQuestion(6, '聞き返す', $sceneLabel, 'Did that make sense?', 'もう少しゆっくり話していただけますか？', 'Could you speak more slowly? を使います。', 'Could you speak a little more slowly?'),
            $this->makeQuestion(7, '変更を頼む', $sceneLabel, 'Do you want to change anything?', '時間を変更できますか？', 'Can we change 〜? を使います。', 'Can we change the time?'),
            $this->makeQuestion(8, '状況を説明する', $sceneLabel, 'What happened?', '少し問題がありました。', 'There was 〜. を使います。', 'There was a small problem.'),
            $this->makeQuestion(9, '感想を伝える', $sceneLabel, 'How was it?', '思っていたより良かったです。', 'better than expected を使います。', 'It was better than I expected.'),
            $this->makeQuestion(10, '締めくくる', $sceneLabel, 'Anything else?', '今日はありがとうございました。', 'Thank you for 〜. を使います。', 'Thank you for your help today.'),
        ];
    }
    // 上級レベルの汎用問題データを返す
    private function makeAdvancedQuestions($sceneLabel)
    {
        return [
            $this->makeQuestion(1, '丁寧に相談する', $sceneLabel, 'How can I assist you?', 'いくつか確認させていただいてもよろしいでしょうか？', 'Would it be possible to 〜? を使います。', 'Would it be possible to confirm a few details?'),
            $this->makeQuestion(2, '希望を詳しく伝える', $sceneLabel, 'What are you looking for?', '可能であれば、より静かな場所を希望します。', 'if available を使います。', 'I’d prefer a quieter place if available.'),
            $this->makeQuestion(3, '背景を説明する', $sceneLabel, 'Could you explain the situation?', '予定が変わったため、調整が必要になりました。', 'due to 〜 を使います。', 'Due to a change in my schedule, I need to make an adjustment.'),
            $this->makeQuestion(4, '選択肢を確認する', $sceneLabel, 'What would you like to do?', '他にどのような選択肢がありますか？', 'available options を使います。', 'What other options are available?'),
            $this->makeQuestion(5, '丁寧に依頼する', $sceneLabel, 'Do you need anything else?', '可能であれば、早めに対応していただけると助かります。', 'I would appreciate it if 〜. を使います。', 'I would appreciate it if you could handle it as soon as possible.'),
            $this->makeQuestion(6, '誤解を防ぐ', $sceneLabel, 'Is that clear?', '念のため、認識が合っているか確認したいです。', 'just to make sure を使います。', 'Just to make sure, I’d like to confirm that we are on the same page.'),
            $this->makeQuestion(7, '変更を依頼する', $sceneLabel, 'Would you like to make a change?', '差し支えなければ、予約内容を変更したいです。', 'if it is not too much trouble を使います。', 'If it is not too much trouble, I’d like to change the reservation.'),
            $this->makeQuestion(8, '問題を説明する', $sceneLabel, 'What seems to be the issue?', '期待していた内容と少し異なっていました。', 'not quite what I expected を使います。', 'It was not quite what I expected.'),
            $this->makeQuestion(9, '意見を伝える', $sceneLabel, 'What do you think?', '全体的には良かったですが、少し改善の余地があります。', 'room for improvement を使います。', 'Overall, it was good, but there is some room for improvement.'),
            $this->makeQuestion(10, '丁寧に締める', $sceneLabel, 'Is there anything else?', '本日はご対応いただきありがとうございました。', 'I appreciate 〜. を使います。', 'I appreciate your assistance today.'),
        ];
    }
    // 問題1件分の配列を組み立てて返す
    private function makeQuestion(
        $number,
        $title,
        $sceneLabel,
        $partnerMessage,
        $question,
        $hint,
        $correctChoice
    ) {
        return [
            'number' => $number,
            'title' => $title,
            'scene_label' => $sceneLabel,
            'partner_message' => $partnerMessage,
            'question' => $question,
            'hint' => $hint,
            'correct_choice' => $correctChoice,
            'wrong_choice_1' => 'I want this.',
            'wrong_choice_2' => 'Please do it.',
            'wrong_choice_3' => 'This is OK.',
            'correct_explanation' => 'この表現は、場面に合った自然な英語です。',
            'incorrect_explanation' => '意味は近くても、より自然で丁寧な表現を選ぶ必要があります。',
        ];
    }
}