import { useEffect } from 'preact/hooks';

export function App() {
    useEffect(() => {
        if (!document.querySelector('df-messenger')) {
            const bot = document.createElement('df-messenger');
            bot.setAttribute('intent', 'WELCOME');
            bot.setAttribute('chat-title', 'RDV Auto');
            bot.setAttribute('agent-id', '038f7a1e-73dc-49e4-a903-7d586b52165b');
            bot.setAttribute('language-code', 'fr');
            bot.setAttribute('mode', 'embedded');
            document.body.appendChild(bot);
        }
    }, []);

    return (
        <div className="chatbox-wrapper">
            <h2 className="text-lg mb-4">Assistance Prise de Rendez-vous</h2>
            <div id="chatbot-container"
                 className="border rounded-xl shadow-md overflow-hidden h-[600px] max-w-[400px] mx-auto"></div>
        </div>
    );
}
