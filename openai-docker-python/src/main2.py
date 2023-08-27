from pathlib import Path
import json

import openai

print("------ openai-docker-python ------")
txt = Path('/api-key/openai-api-key.txt').read_text()

inputTxt = Path('/input.txt').read_text()
openai.api_key = txt

#print ("Models")
#models = openai.Model.list()
#for model in models.data:
#    print(f" - {model.id}")


print ("Chat completion")
messages=[

    {"role": "system", "content": """

You parse answers from questionnaire and analyze the answers. You output to JSON.

Questions are numbered you don't need to repeat the question in JSON. Give them an ID.

From the answers, extract other useful data point as JSON properties.

    """},
    {"role": "user", "content": """

The following questions was asked:

1): Vil kommunen foreta maskinell eller manuell endelig telling av valgresultatet?

2): I hvilken politisk sak ble dette vedtatt?

3): Ved foreløpig opptelling (manuell opptelling), blir opptellingen gjort i valgkretsene? Hvis det er opptelling i valgkretsene, hvordan overfører kommunen resultatet fra valgkretsene inn til valgstyret/valgansvarlig/EVA Admin?

4): Ved foreløpig opptelling (manuell opptelling), hvordan lagrer/arkiverer kommunen resultatet fra opptellingen utenom EVA Admin? Papir? Digitalt dokument? SMS? Blir resultatet journalført?

5): Har kommunen rutiner for å kontrollere resultatet av foreløpig opptelling opp mot det som er synlig på valgresultat-siden til Valgdirektoratet (valgresultat dått no), i valgprotokoll, i medier og lignende?
En slik kontroll vil f.eks. oppdage tastefeil (kommunen legger inn feil resultat i EVA Admin) samt feil i Valgdirektoratets håndtering av resultatet.

6): Tilsvarende som 4) for endelig opptelling (maskinell eller manuell).
Hvordan lagrer kommunen resultatet fra endelig opptelling utenom i EVA Admin/EVA Skanning? Blir resultatet journalført?

7): Tilsvarende som 5) for endelig opptelling (maskinell eller manuell).
Har kommunen rutiner for kontroll av endelig opptelling mot resultat som blir publisert?

"""},
    {"role": "user", "content": """
The following answers was given:

""" + inputTxt + """

"""},

     {"role": "user", "content": """Add answers to JSON. Question 1 to 7.
     
     Also add root level JSON properties with answers to the following questions about the answers:
     
     final_counting_type - Enum: I spørsmål 1, var endelig opptelling "maskinell opptelling" eller "manuell opptelling"?
     
     counting_type_decision_date - Date: I spørsmål 2, hent ut dato for eventuelt vedtak.
     counting_type_decision_case_number - String: I spørsmål 2, hent ut saksnummer for eventuelt vedtak.
     
     prelimitary_counting__any_counting_in_valgkrets - boolean: I spørsmål 3, er det opptelling i valgkretsene?
     prelimitary_counting__transfer_to_eva__do_they_answer - boolean: I spørsmål 3, svarer de på hvordan resultatet overføres til valgstyret/valgansvarlig/EVA Admin?
     prelimitary_counting__transfer_to_eva__type - string: I spørsmål 3, hvordan overfører de resultatet til valgstyret/valgansvarlig/EVA Admin?
     
     prelimitary_counting__is_stored - boolean: I spørsmål 4, lagrer de noe i arkivet?
     prelimitary_counting__what_is_stored - string: I spørsmål 4, hva lagrer de?
     prelimitary_counting__storage_method - string: I spørsmål 4, hvordan lagres det?
     
     prelimitary_counting__process_for_control - boolean: I spørsmål 5, har de rutiner for å kontrollere resultatet?
     prelimitary_counting__process_for_control_type - string: I spørsmål 5, oppsummer eventuelle rutiner for å kontrollere resultatet?
     
     final_counting__is_stored - boolean: I spørsmål 6, lagrer de noe i arkivet?
     final_counting__what_is_stored - string: I spørsmål 6, hva lagrer de?
     final_counting__storage_method - string: I spørsmål 6, hvordan lagres det?
     
     final_counting__process_for_control - boolean: I spørsmål 7, har de rutiner for å kontrollere resultatet?
     final_counting__process_for_control_type - string: I spørsmål 7, oppsummer eventuelle rutiner for å kontrollere resultatet?
     
     Example JSON:
     {
        "answer1": "...",
        "answer2": "...",
        "answer3": "...",
        "answer4": "...",
        "answer5": "...",
        "answer6": "...",
        "answer7": "..."
        "final_counting_type": "[maskinell|manuell]",
        "counting_type_decision_date": "1970-01-01",
        "counting_type_decision_case_number": "...",
        "prelimitary_counting__any_counting_in_valgkrets": true/false,
        "prelimitary_counting__transfer_to_eva__do_they_answer": true/false,
        "prelimitary_counting__transfer_to_eva__type": "...",
        "prelimitary_counting__is_stored": true/false,
        "prelimitary_counting__what_is_stored": "...",
        "prelimitary_counting__storage_method": "...",
        "prelimitary_counting__process_for_control": true/false,
        "prelimitary_counting__process_for_control_type": "...",
        "final_counting__is_stored": true/false,
        "final_counting__what_is_stored": "...",
        "final_counting__storage_method": "...",
        "final_counting__process_for_control": true/false,
        "final_counting__process_for_control_type": "...",
     }
     
"""},

]
print("--------------- INPUT")
print(json.dumps(messages, indent=4))

chat_completion = openai.ChatCompletion.create(model="gpt-3.5-turbo", messages=messages)

print("--------------- OUTPUT")
print(chat_completion)
print("---------------")
print(chat_completion.choices[0].message.content)
