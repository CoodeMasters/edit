<?php

namespace Database\Seeders;

use App\Models\State;


use Illuminate\Database\Seeder;

class StatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //

        $StatesData = [
            ["id" => "1", "code" => "1", "name" => "Adrar", "ar_name" => "أدرار"],
            ["id" => "2", "code" => "2", "name" => "Chlef", "ar_name" => "الشلف"],
            ["id" => "3", "code" => "3", "name" => "Laghouat", "ar_name" => "الأغواط"],
            ["id" => "4", "code" => "4", "name" => "Oum El Bouaghi", "ar_name" => "أم البواقي"],
            ["id" => "5", "code" => "5", "name" => "Batna", "ar_name" => "باتنة"],
            ["id" => "6", "code" => "6", "name" => "Béjaïa", "ar_name" => "بجاية"],
            ["id" => "7", "code" => "7", "name" => "Biskra", "ar_name" => "بسكرة"],
            ["id" => "8", "code" => "8", "name" => "Bechar", "ar_name" => "بشار"],
            ["id" => "9", "code" => "9", "name" => "Blida", "ar_name" => "البليدة"],
            ["id" => "10", "code" => "10", "name" => "Bouira", "ar_name" => "البويرة"],
            ["id" => "11", "code" => "11", "name" => "Tamanrasset", "ar_name" => "تمنراست"],
            ["id" => "12", "code" => "12", "name" => "Tbessa", "ar_name" => "تبسة"],
            ["id" => "13", "code" => "13", "name" => "Tlemcen", "ar_name" => "تلمسان"],
            ["id" => "14", "code" => "14", "name" => "Tiaret", "ar_name" => "تيارت"],
            ["id" => "15", "code" => "15", "name" => "Tizi Ouzou", "ar_name" => "تيزي وزو"],
            ["id" => "16", "code" => "16", "name" => "Alger", "ar_name" => "الجزائر"],
            ["id" => "17", "code" => "17", "name" => "Djelfa", "ar_name" => "الجلفة"],
            ["id" => "18", "code" => "18", "name" => "Jijel", "ar_name" => "جيجل"],
            ["id" => "19", "code" => "19", "name" => "Se9tif", "ar_name" => "سطيف"],
            ["id" => "20", "code" => "20", "name" => "Saefda", "ar_name" => "سعيدة"],
            ["id" => "21", "code" => "21", "name" => "Skikda", "ar_name" => "سكيكدة"],
            ["id" => "22", "code" => "22", "name" => "Sidi Bel Abbes", "ar_name" => "سيدي بلعباس"],
            ["id" => "23", "code" => "23", "name" => "Annaba", "ar_name" => "عنابة"],
            ["id" => "24", "code" => "24", "name" => "Guelma", "ar_name" => "قالمة"],
            ["id" => "25", "code" => "25", "name" => "Constantine", "ar_name" => "قسنطينة"],
            ["id" => "26", "code" => "26", "name" => "Medea", "ar_name" => "المدية"],
            ["id" => "27", "code" => "27", "name" => "Mostaganem", "ar_name" => "مستغانم"],
            ["id" => "28", "code" => "28", "name" => "M'Sila", "ar_name" => "المسيلة"],
            ["id" => "29", "code" => "29", "name" => "Mascara", "ar_name" => "معسكر"],
            ["id" => "30", "code" => "30", "name" => "Ouargla", "ar_name" => "ورقلة"],
            ["id" => "31", "code" => "31", "name" => "Oran", "ar_name" => "وهران"],
            ["id" => "32", "code" => "32", "name" => "El Bayadh", "ar_name" => "البيض"],
            ["id" => "33", "code" => "33", "name" => "Illizi", "ar_name" => "إليزي"],
            ["id" => "34", "code" => "34", "name" => "Bordj Bou Arreridj", "ar_name" => "برج بوعريريج"],
            ["id" => "35", "code" => "35", "name" => "Boumerdes", "ar_name" => "بومرداس"],
            ["id" => "36", "code" => "36", "name" => "El Tarf", "ar_name" => "الطارف"],
            ["id" => "37", "code" => "37", "name" => "Tindouf", "ar_name" => "تندوف"],
            ["id" => "38", "code" => "38", "name" => "Tissemsilt", "ar_name" => "تيسمسيلت"],
            ["id" => "39", "code" => "39", "name" => "El Oued", "ar_name" => "الوادي"],
            ["id" => "40", "code" => "40", "name" => "Khenchela", "ar_name" => "خنشلة"],
            ["id" => "41", "code" => "41", "name" => "Souk Ahras", "ar_name" => "سوق أهراس"],
            ["id" => "42", "code" => "42", "name" => "Tipaza", "ar_name" => "تيبازة"],
            ["id" => "43", "code" => "43", "name" => "Mila", "ar_name" => "ميلة"],
            ["id" => "44", "code" => "44", "name" => "Ain Defla", "ar_name" => "عين الدفلى"],
            ["id" => "45", "code" => "45", "name" => "Naama", "ar_name" => "النعامة"],
            ["id" => "46", "code" => "46", "name" => "Ain Temouchent", "ar_name" => "عين تموشنت"],
            ["id" => "47", "code" => "47", "name" => "Ghardaefa", "ar_name" => "غرداية"],
            ["id" => "48", "code" => "48", "name" => "Relizane", "ar_name" => "غليزان"],
            ["id" => "49", "code" => "49", "name" => "El M'ghair", "ar_name" => "المغير"],
            ["id" => "50", "code" => "50", "name" => "El Menia", "ar_name" => "المنيعة"],
            ["id" => "51", "code" => "51", "name" => "Ouled Djellal", "ar_name" => "أولاد جلال"],
            ["id" => "52", "code" => "52", "name" => "Bordj Baji Mokhtar", "ar_name" => "برج باجي مختار"],
            ["id" => "53", "code" => "53", "name" => "Béni Abbès", "ar_name" => "بني عباس"],
            ["id" => "54", "code" => "54", "name" => "Timimoun", "ar_name" => "تيميمون"],
            ["id" => "55", "code" => "55", "name" => "Touggourt", "ar_name" => "تقرت"],
            ["id" => "56", "code" => "56", "name" => "Djanet", "ar_name" => "جانت"],
            ["id" => "57", "code" => "57", "name" => "In Salah", "ar_name" => "عين صالح"],
            ["id" => "58", "code" => "58", "name" => "In Guezzam", "ar_name" => "عين قزّام"]
        ];

        foreach ($StatesData as $State) {
            State::create($State);
        }
    }
}
