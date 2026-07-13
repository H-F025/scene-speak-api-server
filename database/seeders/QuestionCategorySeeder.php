<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\QuestionCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class QuestionCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 先にカテゴリ自体を登録する
        $this->saveQuestionCategories();
        // 次に、各問題とカテゴリを紐づける
        $this->saveQuestionCategoryAssignments();
    }
    // question_categories テーブルにカテゴリを登録する
    private function saveQuestionCategories(): void
    {
        $categories = [
            [
                'name' => 'あいさつ表現',
                'description' => 'Hello. や Hi. など、会話の始まりで使う表現',
                'sort_order' => 1,
            ],
            [
                'name' => '自己紹介表現',
                'description' => 'My name is 〜. など、自分の情報を伝える表現',
                'sort_order' => 2,
            ],
            [
                'name' => '注文表現',
                'description' => 'Could I get 〜? など、カフェやお店で注文するときの表現',
                'sort_order' => 3,
            ],
            [
                'name' => '支払い表現',
                'description' => 'Can I have the check? など、会計をお願いするときの表現',
                'sort_order' => 4,
            ],
            [
                'name' => '依頼表現',
                'description' => 'Can you 〜? や Could you 〜? など、相手にお願いする表現',
                'sort_order' => 5,
            ],
            [
                'name' => '確認表現',
                'description' => 'Is that OK? や Can I check 〜? など、内容を確認する表現',
                'sort_order' => 6,
            ],
            [
                'name' => '質問表現',
                'description' => 'I have a question. など、質問があることを伝える表現',
                'sort_order' => 7,
            ],
            [
                'name' => '時間・場所を聞く表現',
                'description' => 'What time 〜? や Where is 〜? など、時間や場所を聞く表現',
                'sort_order' => 8,
            ],
            [
                'name' => '聞き返し表現',
                'description' => 'Could you say that again? など、もう一度言ってもらう表現',
                'sort_order' => 9,
            ],
            [
                'name' => '希望を伝える表現',
                'description' => 'I’d like 〜. や I’d prefer 〜. など、自分の希望を伝える表現',
                'sort_order' => 10,
            ],
            [
                'name' => '理由・状況説明表現',
                'description' => 'because や due to 〜 など、理由や状況を説明する表現',
                'sort_order' => 11,
            ],
            [
                'name' => '提案表現',
                'description' => 'Why don’t we 〜? など、相手に提案する表現',
                'sort_order' => 12,
            ],
            [
                'name' => '変更依頼表現',
                'description' => 'Can we change 〜? など、予定や内容の変更をお願いする表現',
                'sort_order' => 13,
            ],
            [
                'name' => '感想・意見表現',
                'description' => 'It was good. や Overall, it was good. など、感想や意見を伝える表現',
                'sort_order' => 14,
            ],
            [
                'name' => 'お礼・別れの表現',
                'description' => 'Thank you. や See you. など、お礼や別れ際に使う表現',
                'sort_order' => 15,
            ],
        ];
        foreach ($categories as $category) {
            QuestionCategory::updateOrCreate(
                [
                    'name' => $category['name'],
                ],
                [
                    'description' => $category['description'],
                    'sort_order' => $category['sort_order'],
                ]
            );
        }
    }
    // question_category_assignments テーブルに、問題とカテゴリの紐づきを登録する
    private function saveQuestionCategoryAssignments(): void
    {
        // すでに登録済みの問題をすべて取得する
        $questions = Question::all();
        foreach ($questions as $question) {
            // 問題タイトルからカテゴリ名を決める
            $categoryName = $this->getCategoryNameByQuestionTitle($question->title);
            // カテゴリ名を決められなかった場合は、何もせず次の問題へ進む
            if ($categoryName === null) {
                continue;
            }
            // カテゴリ名からカテゴリを取得する
            $category = QuestionCategory::where('name', $categoryName)->first();
            // カテゴリが見つからなかった場合も、何もせず次の問題へ進む
            if ($category === null) {
                continue;
            }
            // 問題とカテゴリの紐づきを登録する
            DB::table('question_category_assignments')->updateOrInsert(
                [
                    'question_id' => $question->id,
                    'question_category_id' => $category->id,
                ],
                []
            );
        }
    }
    // 問題タイトルからカテゴリ名を決める
    private function getCategoryNameByQuestionTitle(string $title): ?string
    {
        if ($title === '挨拶する') {
            return 'あいさつ表現';
        }
        if ($title === 'あいさつする') {
            return 'あいさつ表現';
        }
        if ($title === '名前を伝える') {
            return '自己紹介表現';
        }
        if ($title === '注文する') {
            return '注文表現';
        }
        if ($title === 'メニューを見る') {
            return '注文表現';
        }
        if ($title === 'おかわりを頼む') {
            return '注文表現';
        }
        if ($title === '席を確認する') {
            return '注文表現';
        }
        if ($title === '支払いを頼む') {
            return '支払い表現';
        }
        if ($title === 'お願いする') {
            return '依頼表現';
        }
        if ($title === '丁寧に依頼する') {
            return '依頼表現';
        }
        if ($title === '丁寧に相談する') {
            return '依頼表現';
        }
        if ($title === '確認する') {
            return '確認表現';
        }
        if ($title === '選択肢を確認する') {
            return '確認表現';
        }
        if ($title === '誤解を防ぐ') {
            return '確認表現';
        }
        if ($title === '質問する') {
            return '質問表現';
        }
        if ($title === '時間を聞く') {
            return '時間・場所を聞く表現';
        }
        if ($title === '場所を聞く') {
            return '時間・場所を聞く表現';
        }
        if ($title === 'もう一度聞く') {
            return '聞き返し表現';
        }
        if ($title === '聞き返す') {
            return '聞き返し表現';
        }
        if ($title === '希望を伝える') {
            return '希望を伝える表現';
        }
        if ($title === '希望を詳しく伝える') {
            return '希望を伝える表現';
        }
        if ($title === '理由を伝える') {
            return '理由・状況説明表現';
        }
        if ($title === '状況を説明する') {
            return '理由・状況説明表現';
        }
        if ($title === '背景を説明する') {
            return '理由・状況説明表現';
        }
        if ($title === '問題を説明する') {
            return '理由・状況説明表現';
        }
        if ($title === '提案する') {
            return '提案表現';
        }
        if ($title === '変更を頼む') {
            return '変更依頼表現';
        }
        if ($title === '変更を依頼する') {
            return '変更依頼表現';
        }
        if ($title === '感想を伝える') {
            return '感想・意見表現';
        }
        if ($title === '意見を伝える') {
            return '感想・意見表現';
        }
        if ($title === 'お礼を言う') {
            return 'お礼・別れの表現';
        }
        if ($title === '別れを告げる') {
            return 'お礼・別れの表現';
        }
        if ($title === '締めくくる') {
            return 'お礼・別れの表現';
        }
        if ($title === '丁寧に締める') {
            return 'お礼・別れの表現';
        }
        return null;
    }
}