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
        <div>
            <p>Notre assistant est disponible pour prendre un RDV avec vous.</p>
        </div>
    );
}
