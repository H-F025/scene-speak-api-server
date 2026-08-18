<?php

namespace Database\Seeders;

use App\Models\EnglishLevel;
use App\Models\ExpectedExpression;
use App\Models\Question;
use App\Models\QuestionChoice;
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
                    $question = Question::updateOrCreate(
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
                     // 問題に紐づく選択肢を登録する
                    $this->saveQuestionChoices($question, $questionData);

                    // 音声回答の判定に使う想定表現を登録する
                    $this->saveExpectedExpressions($question, $questionData);
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
        if ($themeName === '道を尋ねる') {
            return $this->getDirectionsQuestions($englishLevelCode);
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
    // 道を尋ねるテーマの問題を英語レベルごとに返す
    private function getDirectionsQuestions($englishLevelCode)
    {
        if ($englishLevelCode === 'beginner') {
            return [
                [
                    'number' => 1,
                    'title' => '呼びかける',
                    'scene_label' => '通行人',
                    'partner_message' => 'Yes? Can I help you?',
                    'question' => 'すみません、駅までの道を教えてください。',
                    'hint' => 'Could you tell me the way to 〜? を使います。',
                    'correct_choice' => 'Excuse me, could you tell me the way to the station?',
                    'wrong_choice_1' => 'Station where is go?',
                    'wrong_choice_2' => 'I go station tell me.',
                    'wrong_choice_3' => 'Way station please now.',
                    'correct_explanation' => 'Could you tell me the way to 〜? は道を尋ねるときの基本の表現です。',
                    'incorrect_explanation' => '単語を並べるだけでは伝わりません。Could you tell me the way to 〜? の形を使いましょう。',
                ],
                [
                    'number' => 2,
                    'title' => '場所を尋ねる',
                    'scene_label' => '通行人',
                    'partner_message' => 'Sure, what are you looking for?',
                    'question' => '郵便局を探しています。',
                    'hint' => 'I’m looking for 〜. を使います。',
                    'correct_choice' => 'I’m looking for the post office.',
                    'wrong_choice_1' => 'Post office I want.',
                    'wrong_choice_2' => 'Where post office me.',
                    'wrong_choice_3' => 'Looking is post office.',
                    'correct_explanation' => 'I’m looking for 〜. は探している場所を伝える自然な表現です。',
                    'incorrect_explanation' => '語順が崩れると伝わりません。I’m looking for 〜. の形を使いましょう。',
                ],
                [
                    'number' => 3,
                    'title' => '距離を尋ねる',
                    'scene_label' => '通行人',
                    'partner_message' => 'It’s not too far from here.',
                    'question' => '歩いて行けますか？',
                    'hint' => 'Can I walk there? を使います。',
                    'correct_choice' => 'Can I walk there?',
                    'wrong_choice_1' => 'Walk is possible there.',
                    'wrong_choice_2' => 'I walking go there yes.',
                    'wrong_choice_3' => 'There walk can.',
                    'correct_explanation' => 'Can I walk there? は「歩いて行けますか」を尋ねる自然な表現です。',
                    'incorrect_explanation' => '動詞の位置を意識しましょう。Can I 〜? の形が基本です。',
                ],
                [
                    'number' => 4,
                    'title' => '方向を確認する',
                    'scene_label' => '通行人',
                    'partner_message' => 'Go straight and turn right.',
                    'question' => '右に曲がるんですね？',
                    'hint' => 'Turn right, right? のように確認できます。',
                    'correct_choice' => 'Turn right, right?',
                    'wrong_choice_1' => 'Right turn is yes?',
                    'wrong_choice_2' => 'I turn right ok?',
                    'wrong_choice_3' => 'Right is turn here?',
                    'correct_explanation' => 'Turn right, right? は聞いた内容を確認する自然な言い方です。',
                    'incorrect_explanation' => '確認するときは相手の言葉を繰り返すと伝わりやすいです。',
                ],
                [
                    'number' => 5,
                    'title' => '目印を尋ねる',
                    'scene_label' => '通行人',
                    'partner_message' => 'You’ll see a big park on your left.',
                    'question' => '公園が目印なんですね。',
                    'hint' => 'a landmark を使うと表現できます。',
                    'correct_choice' => 'The park is a landmark, right?',
                    'wrong_choice_1' => 'Park mark is here?',
                    'wrong_choice_2' => 'Is park sign yes?',
                    'wrong_choice_3' => 'Landmark park is?',
                    'correct_explanation' => 'a landmark は「目印」という意味でよく使われます。',
                    'incorrect_explanation' => 'landmarkの位置を意識しましょう。The park is a landmark, right? が自然です。',
                ],
                [
                    'number' => 6,
                    'title' => '所要時間を尋ねる',
                    'scene_label' => '通行人',
                    'partner_message' => 'It takes about ten minutes.',
                    'question' => 'どのくらい時間がかかりますか？',
                    'hint' => 'How long does it take? を使います。',
                    'correct_choice' => 'How long does it take?',
                    'wrong_choice_1' => 'Time how long take?',
                    'wrong_choice_2' => 'How much time is it long?',
                    'wrong_choice_3' => 'Take time how is?',
                    'correct_explanation' => 'How long does it take? は所要時間を尋ねる基本の表現です。',
                    'incorrect_explanation' => 'How long does it take? の語順を守りましょう。',
                ],
                [
                    'number' => 7,
                    'title' => 'お礼を言う',
                    'scene_label' => '通行人',
                    'partner_message' => 'You can’t miss it.',
                    'question' => '教えてくれてありがとうございます。',
                    'hint' => 'Thank you for 〜. を使います。',
                    'correct_choice' => 'Thank you for telling me.',
                    'wrong_choice_1' => 'Thanks tell me you.',
                    'wrong_choice_2' => 'I thank telling.',
                    'wrong_choice_3' => 'You tell thanks me.',
                    'correct_explanation' => 'Thank you for 〜. はお礼を伝える基本の表現です。',
                    'incorrect_explanation' => 'Thank you for 〜ing の形を守りましょう。',
                ],
                [
                    'number' => 8,
                    'title' => '反対方向を確認する',
                    'scene_label' => '通行人',
                    'partner_message' => 'Actually, it’s the other way.',
                    'question' => '反対方向なんですね。',
                    'hint' => 'the other way を使います。',
                    'correct_choice' => 'It’s the other way, right?',
                    'wrong_choice_1' => 'Other way is right yes?',
                    'wrong_choice_2' => 'Way other here is?',
                    'wrong_choice_3' => 'Is way opposite here?',
                    'correct_explanation' => 'the other way は「反対方向」という意味です。',
                    'incorrect_explanation' => 'It’s the other way, right? の形で確認しましょう。',
                ],
                [
                    'number' => 9,
                    'title' => '近くのお店を尋ねる',
                    'scene_label' => '通行人',
                    'partner_message' => 'There’s a convenience store nearby.',
                    'question' => '近くにコンビニがあるんですね。',
                    'hint' => 'nearby を使います。',
                    'correct_choice' => 'There’s a convenience store nearby, right?',
                    'wrong_choice_1' => 'Nearby store convenience is?',
                    'wrong_choice_2' => 'Store is near here yes?',
                    'wrong_choice_3' => 'Convenience nearby store right.',
                    'correct_explanation' => 'nearby は「近くに」という意味でよく使われます。',
                    'incorrect_explanation' => 'nearbyの位置に注意しましょう。文末に置くと自然です。',
                ],
                [
                    'number' => 10,
                    'title' => '別れる',
                    'scene_label' => '通行人',
                    'partner_message' => 'Good luck finding it!',
                    'question' => 'ありがとうございます。行ってきます。',
                    'hint' => 'Thank you. I’ll go now. を使います。',
                    'correct_choice' => 'Thank you. I’ll go now.',
                    'wrong_choice_1' => 'Thanks go now I.',
                    'wrong_choice_2' => 'I now go thanks.',
                    'wrong_choice_3' => 'Go I will thanks now.',
                    'correct_explanation' => 'Thank you. I’ll go now. は別れ際の自然な表現です。',
                    'incorrect_explanation' => 'I’ll go now. の語順を守りましょう。',
                ],
            ];
        }
        if ($englishLevelCode === 'intermediate') {
            return [
                [
                    'number' => 1,
                    'title' => '丁寧に尋ねる',
                    'scene_label' => '通行人',
                    'partner_message' => 'Sure, where would you like to go?',
                    'question' => '美術館までの行き方を教えていただけますか？',
                    'hint' => 'Could you tell me how to get to 〜? を使います。',
                    'correct_choice' => 'Could you tell me how to get to the museum?',
                    'wrong_choice_1' => 'Tell me museum way please go.',
                    'wrong_choice_2' => 'How museum get is way?',
                    'wrong_choice_3' => 'Museum way tell please me.',
                    'correct_explanation' => 'Could you tell me how to get to 〜? は道順を丁寧に尋ねる表現です。',
                    'incorrect_explanation' => 'get to 〜 の形を崩さないようにしましょう。',
                ],
                [
                    'number' => 2,
                    'title' => '目的地を詳しく伝える',
                    'scene_label' => '通行人',
                    'partner_message' => 'There are a few museums around here.',
                    'question' => '駅の近くにある美術館を探しています。',
                    'hint' => 'the one near 〜 を使います。',
                    'correct_choice' => 'I’m looking for the one near the station.',
                    'wrong_choice_1' => 'Museum near station I looking.',
                    'wrong_choice_2' => 'Near station is museum want.',
                    'wrong_choice_3' => 'Looking museum station near am.',
                    'correct_explanation' => 'the one near 〜 で「〜の近くにあるもの」と特定できます。',
                    'incorrect_explanation' => 'I’m looking for 〜. の文型を守りましょう。',
                ],
                [
                    'number' => 3,
                    'title' => 'ルートを比較する',
                    'scene_label' => '通行人',
                    'partner_message' => 'You can either walk or take the bus.',
                    'hint' => 'Which is faster, 〜 or 〜? を使います。',
                    'question' => '歩くのとバスに乗るのとでは、どちらが早いですか？',
                    'correct_choice' => 'Which is faster, walking or taking the bus?',
                    'wrong_choice_1' => 'Faster walking bus which is?',
                    'wrong_choice_2' => 'Walk bus fast which more?',
                    'wrong_choice_3' => 'Which fast is walk or bus?',
                    'correct_explanation' => 'Which is faster, A or B? は二つを比較して尋ねる表現です。',
                    'incorrect_explanation' => '比較の文は Which is faster, A or B? の形を守りましょう。',
                ],
                [
                    'number' => 4,
                    'title' => '迷ったことを伝える',
                    'scene_label' => '通行人',
                    'partner_message' => 'Are you lost?',
                    'question' => 'はい、少し道に迷ってしまいました。',
                    'hint' => 'I’m a little lost. を使います。',
                    'correct_choice' => 'Yes, I’m a little lost.',
                    'wrong_choice_1' => 'Yes little I lost am.',
                    'wrong_choice_2' => 'I am yes lost little.',
                    'wrong_choice_3' => 'Lost yes I’m little am.',
                    'correct_explanation' => 'I’m a little lost. は迷っていることを伝える自然な表現です。',
                    'incorrect_explanation' => 'I’m a little lost. の語順を守りましょう。',
                ],
                [
                    'number' => 5,
                    'title' => '目印を詳しく尋ねる',
                    'scene_label' => '通行人',
                    'partner_message' => 'Look for a tall building with a red sign.',
                    'question' => '赤い看板のある高い建物が目印ということですね。',
                    'hint' => 'a building with 〜 を使います。',
                    'correct_choice' => 'So the landmark is a tall building with a red sign, right?',
                    'wrong_choice_1' => 'Building red sign is landmark tall?',
                    'wrong_choice_2' => 'Landmark tall red is building?',
                    'wrong_choice_3' => 'Red sign tall building landmark right?',
                    'correct_explanation' => 'a building with 〜 で建物の特徴を説明できます。',
                    'incorrect_explanation' => '修飾語の順番に注意しましょう。a tall building with a red sign が自然です。',
                ],
                [
                    'number' => 6,
                    'title' => '乗り換えを尋ねる',
                    'scene_label' => '通行人',
                    'partner_message' => 'You’ll need to change trains at the next station.',
                    'question' => '次の駅で電車を乗り換える必要があるんですね。',
                    'hint' => 'change trains を使います。',
                    'correct_choice' => 'So I need to change trains at the next station, right?',
                    'wrong_choice_1' => 'Next station change trains need I?',
                    'wrong_choice_2' => 'Change I trains station next?',
                    'wrong_choice_3' => 'Trains change need next station I?',
                    'correct_explanation' => 'change trains は「電車を乗り換える」という意味です。',
                    'incorrect_explanation' => 'need to 〜 の語順を守りましょう。',
                ],
                [
                    'number' => 7,
                    'title' => '逆方向だったことを伝える',
                    'scene_label' => '通行人',
                    'partner_message' => 'Wait, I think you’re heading the wrong way.',
                    'question' => 'え、逆方向に進んでいたんですか？',
                    'hint' => 'the wrong way を使います。',
                    'correct_choice' => 'Oh, was I heading the wrong way?',
                    'wrong_choice_1' => 'Wrong way heading was I?',
                    'wrong_choice_2' => 'I was way wrong heading?',
                    'wrong_choice_3' => 'Heading wrong I way was?',
                    'correct_explanation' => 'heading the wrong way は「逆方向に進んでいる」という意味です。',
                    'incorrect_explanation' => 'was I heading 〜? の疑問文の語順を守りましょう。',
                ],
                [
                    'number' => 8,
                    'title' => '別のルートを尋ねる',
                    'scene_label' => '通行人',
                    'partner_message' => 'There’s a shortcut through the park.',
                    'question' => '公園を通る近道もあるんですね。',
                    'hint' => 'a shortcut through 〜 を使います。',
                    'correct_choice' => 'So there’s a shortcut through the park too, right?',
                    'wrong_choice_1' => 'Park shortcut through is too?',
                    'wrong_choice_2' => 'Through park shortcut also is?',
                    'wrong_choice_3' => 'Shortcut park through too is?',
                    'correct_explanation' => 'a shortcut through 〜 で「〜を通る近道」を表せます。',
                    'incorrect_explanation' => 'shortcutの位置を意識し、a shortcut through the park の形を守りましょう。',
                ],
                [
                    'number' => 9,
                    'title' => '詳しく感謝を伝える',
                    'scene_label' => '通行人',
                    'partner_message' => 'I hope you find it easily.',
                    'question' => '詳しく教えていただき、本当に助かりました。',
                    'hint' => 'That really helped me. を使います。',
                    'correct_choice' => 'Thank you for the detailed directions. That really helped me.',
                    'wrong_choice_1' => 'Detailed directions thanks helped really.',
                    'wrong_choice_2' => 'Really helped me thanks detailed.',
                    'wrong_choice_3' => 'Directions detailed thanks me helped.',
                    'correct_explanation' => 'That really helped me. は感謝の気持ちを強調する表現です。',
                    'incorrect_explanation' => 'Thank you for 〜. と That really helped me. を分けて使いましょう。',
                ],
                [
                    'number' => 10,
                    'title' => '到着後の報告',
                    'scene_label' => '通行人',
                    'partner_message' => 'Did you find the place okay?',
                    'question' => 'はい、教えてもらった通りに行けました。',
                    'hint' => 'just as you told me を使います。',
                    'correct_choice' => 'Yes, I got there just as you told me.',
                    'wrong_choice_1' => 'Told you just there I got.',
                    'wrong_choice_2' => 'I there got just told you.',
                    'wrong_choice_3' => 'Just you told there I got.',
                    'correct_explanation' => 'just as you told me は「教えてもらった通りに」という意味です。',
                    'incorrect_explanation' => 'I got there just as you told me. の語順を守りましょう。',
                ],
            ];
        }
        return [
            [
                'number' => 1,
                'title' => '丁寧に道を尋ねる',
                'scene_label' => '通行人',
                'partner_message' => 'Of course, how can I assist you?',
                'question' => '恐れ入りますが、市立図書館への行き方を教えていただけますでしょうか？',
                'hint' => 'Would you happen to know 〜? を使います。',
                'correct_choice' => 'Excuse me, would you happen to know how to get to the city library?',
                'wrong_choice_1' => 'City library way get would happen know?',
                'wrong_choice_2' => 'How get library city would know happen?',
                'wrong_choice_3' => 'Would library city know get how happen?',
                'correct_explanation' => 'Would you happen to know 〜? はより丁寧に尋ねる表現です。',
                'incorrect_explanation' => 'would happen to know の語順を崩さないようにしましょう。',
            ],
            [
                'number' => 2,
                'title' => '目的を詳しく説明する',
                'scene_label' => '通行人',
                'partner_message' => 'Sure, may I ask why you’re heading there?',
                'question' => '会議に間に合うように、できるだけ早く着きたいんです。',
                'hint' => 'in order to 〜 を使います。',
                'correct_choice' => 'I’d like to get there as soon as possible in order to make it to my meeting on time.',
                'wrong_choice_1' => 'Meeting time make get soon possible order.',
                'wrong_choice_2' => 'In order meeting soon get possible.',
                'wrong_choice_3' => 'As soon possible in order meeting get.',
                'correct_explanation' => 'in order to 〜 で目的を説明できます。',
                'incorrect_explanation' => 'as soon as possible と in order to 〜 の位置を意識しましょう。',
            ],
            [
                'number' => 3,
                'title' => '複雑なルートを確認する',
                'scene_label' => '通行人',
                'partner_message' => 'It’s a bit complicated, so let me explain carefully.',
                'question' => '分かりやすく説明していただけると助かります。',
                'hint' => 'I’d appreciate it if 〜. を使います。',
                'correct_choice' => 'I’d appreciate it if you could explain it clearly.',
                'wrong_choice_1' => 'Explain clearly appreciate could you if.',
                'wrong_choice_2' => 'Clearly explain I appreciate could.',
                'wrong_choice_3' => 'If you clearly appreciate explain could.',
                'correct_explanation' => 'I’d appreciate it if 〜. は丁寧な依頼の表現です。',
                'incorrect_explanation' => 'I’d appreciate it if you could 〜. の形を守りましょう。',
            ],
            [
                'number' => 4,
                'title' => '交通手段を相談する',
                'scene_label' => '通行人',
                'partner_message' => 'You could take a taxi if you’re in a hurry.',
                'question' => '急いでいるとはいえ、できれば歩いて行きたいです。',
                'hint' => 'even though 〜 を使います。',
                'correct_choice' => 'Even though I’m in a hurry, I’d prefer to walk if possible.',
                'wrong_choice_1' => 'Hurry though even walk prefer possible.',
                'wrong_choice_2' => 'I’d prefer walk even hurry though possible.',
                'wrong_choice_3' => 'Possible walk prefer even though hurry.',
                'correct_explanation' => 'even though 〜 は「〜とはいえ」という譲歩を表します。',
                'incorrect_explanation' => 'even though の後には文が続く形を守りましょう。',
            ],
            [
                'number' => 5,
                'title' => '道に迷った状況を説明する',
                'scene_label' => '通行人',
                'partner_message' => 'What seems to be the problem?',
                'question' => '地図の通りに進んだつもりが、いつの間にか違う道に入ってしまったようです。',
                'hint' => 'without realizing it を使います。',
                'correct_choice' => 'I followed the map, but it seems I ended up on the wrong street without realizing it.',
                'wrong_choice_1' => 'Map followed wrong street realizing without ended.',
                'wrong_choice_2' => 'Without realizing wrong street ended followed map.',
                'wrong_choice_3' => 'Ended street wrong without map followed realizing.',
                'correct_explanation' => 'without realizing it は「気づかないうちに」という意味です。',
                'incorrect_explanation' => '文全体の流れを意識し、I followed 〜, but it seems 〜. の形を守りましょう。',
            ],
            [
                'number' => 6,
                'title' => '目印を詳しく確認する',
                'scene_label' => '通行人',
                'partner_message' => 'There should be a fountain right in front of the entrance.',
                'question' => '入り口の正面にある噴水を見逃さないようにします。',
                'hint' => 'make sure not to miss 〜 を使います。',
                'correct_choice' => 'I’ll make sure not to miss the fountain right in front of the entrance.',
                'wrong_choice_1' => 'Fountain miss sure not entrance front make.',
                'wrong_choice_2' => 'Not miss make sure fountain entrance front.',
                'wrong_choice_3' => 'Entrance front fountain sure not miss make.',
                'correct_explanation' => 'make sure not to miss 〜 は「〜を見逃さないようにする」という表現です。',
                'incorrect_explanation' => 'make sure not to 〜 の語順を守りましょう。',
            ],
            [
                'number' => 7,
                'title' => '想定外の状況を伝える',
                'scene_label' => '通行人',
                'partner_message' => 'Is everything all right?',
                'question' => '実は、工事のせいで案内された道が通行止めになっていたんです。',
                'hint' => 'due to construction を使います。',
                'correct_choice' => 'Actually, the road I was told to take was closed due to construction.',
                'wrong_choice_1' => 'Construction due closed road told take was.',
                'wrong_choice_2' => 'Road closed due construction told take was.',
                'wrong_choice_3' => 'Due construction road closed take told was.',
                'correct_explanation' => 'due to construction は「工事のせいで」という意味です。',
                'incorrect_explanation' => 'the road I was told to take was closed の語順を守りましょう。',
            ],
            [
                'number' => 8,
                'title' => '代替案を丁寧に相談する',
                'scene_label' => '通行人',
                'partner_message' => 'In that case, let’s find another route.',
                'question' => '何か他に良い方法があれば、ぜひ教えていただきたいです。',
                'hint' => 'if there’s any other way を使います。',
                'correct_choice' => 'If there’s any other way, I’d really like to know about it.',
                'wrong_choice_1' => 'Other way any if know really like.',
                'wrong_choice_2' => 'Really like know if way other any.',
                'wrong_choice_3' => 'Know like really if any other way.',
                'correct_explanation' => 'if there’s any other way は「他に方法があれば」という意味です。',
                'incorrect_explanation' => 'if there’s any other way, I’d like to 〜. の形を守りましょう。',
            ],
            [
                'number' => 9,
                'title' => '感謝と見通しを伝える',
                'scene_label' => '通行人',
                'partner_message' => 'I hope this helps you get there smoothly.',
                'question' => 'おかげさまで、今度は迷わずに行けそうです。',
                'hint' => 'thanks to you を使います。',
                'correct_choice' => 'Thanks to you, I think I can get there without getting lost this time.',
                'wrong_choice_1' => 'You thanks get lost without think can.',
                'wrong_choice_2' => 'Without lost getting thanks you think can.',
                'wrong_choice_3' => 'Getting lost without you thanks can think.',
                'correct_explanation' => 'thanks to you は「あなたのおかげで」という意味です。',
                'incorrect_explanation' => 'Thanks to you, I think 〜. の形を守りましょう。',
            ],
            [
                'number' => 10,
                'title' => '丁寧に締めくくる',
                'scene_label' => '通行人',
                'partner_message' => 'No problem at all. Have a safe trip.',
                'question' => '本当に助けていただき、心から感謝しています。',
                'hint' => 'I truly appreciate 〜. を使います。',
                'correct_choice' => 'I truly appreciate your help. Thank you so much.',
                'wrong_choice_1' => 'Appreciate truly help your much thank so.',
                'wrong_choice_2' => 'Your help truly appreciate thank so much.',
                'wrong_choice_3' => 'Thank so much your appreciate truly help.',
                'correct_explanation' => 'I truly appreciate 〜. は深い感謝を伝える表現です。',
                'incorrect_explanation' => 'I truly appreciate your help. の語順を守りましょう。',
            ],
        ];
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

    // 問題に紐づく選択肢を登録する
    private function saveQuestionChoices(Question $question, array $questionData): void
    {
        // 選択肢を作成する
        $choices = $this->makeQuestionChoices($questionData);

        foreach ($choices as $choice) {
            QuestionChoice::updateOrCreate(
                [
                    'question_id' => $question->id,
                    'sort_order' => $choice['sort_order'],
                ],
                [
                    'content' => $choice['content'],
                    'is_correct' => $choice['is_correct'],
                ]
            );
        }
    }

    // 選択肢データを作成する
    private function makeQuestionChoices(array $questionData): array
    {
        // 正解の位置を問題番号ごとにずらす
        // これにより、毎回1番目が正解になることを防ぐ
        $correctSortOrder = (($questionData['number'] - 1) % 4) + 1;

        $wrongChoices = [
            $questionData['wrong_choice_1'],
            $questionData['wrong_choice_2'],
            $questionData['wrong_choice_3'],
        ];

        $choices = [];
        $wrongChoiceIndex = 0;

        for ($sortOrder = 1; $sortOrder <= 4; $sortOrder++) {
            if ($sortOrder === $correctSortOrder) {
                $choices[] = [
                    'content' => $questionData['correct_choice'],
                    'is_correct' => true,
                    'sort_order' => $sortOrder,
                ];

                continue;
            }

            $choices[] = [
                'content' => $wrongChoices[$wrongChoiceIndex],
                'is_correct' => false,
                'sort_order' => $sortOrder,
            ];

            $wrongChoiceIndex++;
        }

        return $choices;
    }

    // 音声回答の判定に使う想定表現を登録する。
    // 正解の選択肢を模範解答 (is_primary) として登録し、言い換え表現は必要に応じて追加する
    private function saveExpectedExpressions(Question $question, array $questionData): void
    {
        ExpectedExpression::updateOrCreate(
            ['question_id' => $question->id, 'text' => $questionData['correct_choice']],
            ['is_primary' => true]
        );

        foreach ($questionData['alternative_expressions'] ?? [] as $text) {
            ExpectedExpression::updateOrCreate(
                ['question_id' => $question->id, 'text' => $text],
                ['is_primary' => false]
            );
        }
    }
}
