import { useEffect, useState } from 'preact/hooks';
import './style.css';
import 'leaflet/dist/leaflet.css';
import LeafletMap from './LeafletMap.tsx';

type Step = {
    step: string;
    message: string;
    type: 'text' | 'confirm' | 'checkbox';
    options?: string[];
    data?: any;
};

export function App() {
    const [messages, setMessages] = useState<string[]>([]);
    const [currentStep, setCurrentStep] = useState<Step | null>(null);
    const [inputValue, setInputValue] = useState('');
    const [selected, setSelected] = useState<string[]>([]);

    useEffect(() => {
        fetchStep('start');
    }, []);

    const fetchStep = async (step: string, input?: string[] | string) => {
        const res = await fetch('/api/chatbot/step', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ step, input }),
        });

        const json = await res.json();
        setMessages(prev => [...prev, json.message]);
        setCurrentStep(json);
        setInputValue('');
        setSelected([]);
    };

    const handleSubmit = (e: Event) => {
        e.preventDefault();
        const input = currentStep?.type === 'checkbox' ? selected : inputValue;
        fetchStep(currentStep!.step, input);
    };

    return (
        <div class="chatbot">
            <div class="chatbot__messages">
                {messages.map((msg, index) => (
                    <div key={index} class="chatbot__message" dangerouslySetInnerHTML={{ __html: msg }}
                    />
                ))}
            </div>

            {currentStep?.step === 'choose_garage' && currentStep?.data?.garages && (
                <LeafletMap
                    garages={currentStep.data.garages}
                    onGarageSelect={(garage) => {
                        fetchStep('choose_garage', [`${garage.name} – ${garage.address} (${garage.zipcode} ${garage.city})`]);
                    }}
                />
            )}

            {currentStep?.type === 'text' && (
                <form onSubmit={handleSubmit} class="chatbot__form">
                    <input
                        type="text"
                        value={inputValue}
                        onInput={(e) => setInputValue((e.target as HTMLInputElement).value)}
                        placeholder="Votre réponse..."
                        class="chatbot__input"
                    />
                    <button type="submit" class="chatbot__button">Envoyer</button>
                </form>
            )}

            {currentStep?.type === 'confirm' && (
                <div class="chatbot__confirm">
                    <button onClick={() => fetchStep(currentStep.step, 'oui')}>Oui</button>
                    <button onClick={() => fetchStep(currentStep.step, 'non')}>Non</button>
                </div>
            )}

            {currentStep?.type === 'checkbox' && (
                <form onSubmit={handleSubmit} class="chatbot__form chatbot__checkboxes">
                    {currentStep.options?.map((op, i) => (
                        <label key={i} class="chatbot__checkbox-label">
                            <input
                                type="checkbox"
                                value={op}
                                checked={selected.includes(op)}
                                onChange={(e) => {
                                    const value = (e.target as HTMLInputElement).value;
                                    setSelected(prev =>
                                        prev.includes(value)
                                            ? prev.filter(v => v !== value)
                                            : [...prev, value]
                                    );
                                }}
                            />
                            {op}
                        </label>
                    ))}
                    <button type="submit" class="chatbot__button">Valider</button>
                </form>
            )}
        </div>
    );
}
