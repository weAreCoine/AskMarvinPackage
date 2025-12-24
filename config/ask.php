<?php

// config for Marvin/Ask
return [
    'town' => env('TOWN'),
    'town_site_base_url' => env('TOWN_SITE_BASE_URL'),
    'preserve_history_during_chat' => (bool)env('PRESERVE_HISTORY_DURING_CHAT', false),
    'services' => [
        'whatsapp' => [
            'api_key' => env('WHATSAPP_API_SECRET'),
            'verification_token' => env('WHATSAPP_VERIFICATION_TOKEN'),
            'graph_version' => env('WHATSAPP_GRAPH_VERSION', 'v24.0'),
            'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
            'send_waiting_message' => (bool)env('WHATSAPP_SEND_WAITING_MESSAGE', false),
        ],

        'prism' => [
            'chat_model_limits' => [
                'tpm' => (int)env('PRISM_CHAT_MODEL_TPM', 1),
                'rpm' => (int)env('PRISM_CHAT_MODEL_RPM', 1),
                'tpd' => (int)env('PRISM_CHAT_MODEL_TPD', 1),
            ],

            'chat' => [
                'provider' => env('PRISM_CHAT_PROVIDER', 'ollama'),
                'model' => env('PRISM_CHAT_MODEL', 'mistral:7b'),
            ],
            'embed' => [
                'provider' => env('PRISM_EMBED_PROVIDER', 'ollama'),
                'model' => env('PRISM_EMBED_MODEL', 'mistral:7b'),
            ],
            'speech_to_text' => [
                'provider' => env('PRISM_SPEECH_TO_TEXT_PROVIDER', 'openai'),
                'model' => env('PRISM_SPEECH_TO_TEXT_MODEL', 'gpt-4o-transcribe'),
            ],
            'text_to_speech' => [
                'provider' => env('PRISM_SPEECH_TO_TEXT_PROVIDER', 'openai'),
                'model' => env('PRISM_SPEECH_TO_TEXT_MODEL', 'gpt-4o-transcribe'),
            ],
            'low_difficulty_tasks' => [
                'provider' => env('PRISM_LOW_DIFFICULTY_TASKS_PROVIDER', 'openai'),
                'model' => env('PRISM_LOW_DIFFICULTY_TASKS_MODEL', 'gpt-3.5-turbo'),
            ]

        ],
        'pinecone' => [
            'api_key' => env('PINECONE_API_KEY', ''),
            'index_host' => env('PINECONE_INDEX_HOST', ''),
            'index_name' => env('PINECONE_INDEX_NAME', ''),
            'namespace' => env('PINECONE_NAMESPACE', ''),
            'rerank_model' => env('PINECONE_RERANK_MODEL', 'bge-reranker-v2-m3'),
        ],
        'langfuse' => [
            'key' => env('LANGFUSE_PK'),
            'secret' => env('LANGFUSE_SK'),
            'host' => env('LANGFUSE_HOST'),
        ],
        'google_api' => [
            'sa_key_path' => storage_path('app/private/keys/marvin-474716-7f63fa7ad787.json'),
            'workspace_domain' => env('GOOGLE_WORKSPACE_DOMAIN', 'askmarvin.it'),
            'gmail_scopes' => explode(' ', env('GOOGLE_GMAIL_SCOPES', 'https://www.googleapis.com/auth/gmail.readonly')),
        ]
    ]
];
