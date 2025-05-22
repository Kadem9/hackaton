import {useEffect, useState} from 'preact/hooks';
import './style.css';
import 'leaflet/dist/leaflet.css';
import LeafletMap from "./LeafletMap.tsx";
import {ComponentChild, VNode} from 'preact';

type Step = {
    step: string;
    message: string;
    type: 'text' | 'confirm' | 'checkbox' | 'radio' | 'confirm_appointment' | 'timeslot';
    options?: string[];
    data?: any;
};

type ChatMessage = {
    sender: 'user' | 'bot';
    text: string;
};

export function App() {
    const [messages, setMessages] = useState<ChatMessage[]>([]);
    const [currentStep, setCurrentStep] = useState<Step | null>(null);
    const [inputValue, setInputValue] = useState('');
    const [selectedCheckboxes, setSelectedCheckboxes] = useState<string[]>([]);
    const [selectedRadio, setSelectedRadio] = useState<string>('');

    useEffect(() => {
        fetchStep('start');
    }, []);

    const fetchStep = async (step: string, input?: string[] | string) => {
        if (input) {
            const userMessage = Array.isArray(input) ? input.join(', ') : input;
            setMessages(prev => [
                ...prev,
                {sender: 'user', text: userMessage}
            ]);
        }

        const payload: any = {step, input};
        if (currentStep?.data) {
            payload.data = currentStep.data;
        }

        const res = await fetch('/api/chatbot/step', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });

        const json: Step = await res.json();
        setMessages(prev => [
            ...prev,
            {sender: 'bot', text: json.message}
        ]);
        setCurrentStep(json);
        setInputValue('');
        setSelectedCheckboxes([]);
        setSelectedRadio('');
    };

    const handleSubmit = (e: Event) => {
        e.preventDefault();
        let input;

        if (currentStep?.type === 'checkbox') {
            input = selectedCheckboxes;
        } else if (currentStep?.type === 'radio') {
            input = selectedRadio;
        } else {
            input = inputValue;
        }

        fetchStep(currentStep!.step, input);
    };


    return (
        <div class="chatbot">
            <div class="chatbot__messages">
                {messages.map((msg, index) => (
                    <div
                        key={index}
                        class={`chatbot__message-container ${
                            msg.sender === 'user' ? 'align-right' : 'align-left'
                        }`}
                    >
                        <div class="chatbot__sender">
                            {msg.sender === 'user' ? 'Vous' : 'Chatbot'}
                        </div>
                        <div
                            class={`chatbot__message ${
                                msg.sender === 'user' ? 'user-message' : 'bot-message'
                            }`}
                            dangerouslySetInnerHTML={{__html: msg.text}}
                        />
                    </div>
                ))}
            </div>

            {currentStep?.step === 'choose_garage' && currentStep?.data?.garages && (
                <LeafletMap
                    garages={currentStep.data.garages}
                    onGarageSelect={garage =>
                        fetchStep('choose_garage', [
                            `${garage.name} – ${garage.address} (${garage.zipcode} ${garage.city})`
                        ])
                    }
                />
            )}

            {currentStep?.type === 'text' && (
                <form onSubmit={handleSubmit} class="chatbot__form">
                    <input
                        type="text"
                        value={inputValue}
                        onInput={e => setInputValue((e.target as HTMLInputElement).value)}
                        placeholder="Votre réponse..."
                        class="chatbot__input"
                    />
                    <button type="submit" class="chatbot__button">
                        Envoyer
                    </button>
                </form>
            )}

            {currentStep?.type === 'confirm' && (
                <div class="chatbot__confirm">
                    <button onClick={() => fetchStep(currentStep.step, 'oui')}>
                        Oui
                    </button>
                    <button onClick={() => fetchStep(currentStep.step, 'non')}>
                        Non
                    </button>
                </div>
            )}

            {currentStep?.type === 'confirm_appointment' && (
                <div class="chatbot__confirm">
                    <button onClick={() => fetchStep(currentStep.step, 'oui')}>
                        Date précise
                    </button>
                    <button onClick={() => fetchStep(currentStep.step, 'non')}>
                        Liste des créneaux
                    </button>
                </div>
            )}

            {currentStep?.data?.images && (
                <div class="chatbot__images">
                    {currentStep.data.images.map((url: string, i: number) => (
                        <img
                            key={i}
                            src={url}
                            alt="voiture"
                            class="chatbot__image"
                        />
                    ))}
                </div>
            )}
            {currentStep?.type === 'checkbox' && (
                <form onSubmit={handleSubmit} class="chatbot__form chatbot__checkboxes">
                    {currentStep.options?.map((op, i) => (
                        <label key={i} class="chatbot__checkbox-label">
                            <input
                                type="checkbox"
                                value={op}
                                checked={selectedCheckboxes.includes(op)}
                                onChange={e => {
                                    const v = (e.target as HTMLInputElement).value;
                                    setSelectedCheckboxes(prev =>
                                        prev.includes(v)
                                            ? prev.filter(x => x !== v)
                                            : [...prev, v]
                                    );
                                }}
                            />
                            {op}
                        </label>
                    ))}
                    <button type="submit" class="chatbot__button">
                        Valider
                    </button>
                </form>
            )}

            {currentStep?.type === 'timeslot' && currentStep?.data?.timeslots && (
                <div class="chatbot__timeslots">
                    {currentStep.data.timeslots.map((slot: {
                        label: string | number | bigint | boolean | object | ComponentChild[] | VNode<any> | null | undefined;
                        times: string[];
                    }, i: unknown) => (
                        <div key={i} class="timeslot-day">
                            <div class="timeslot-day-label">{slot.label}</div>
                            <div class="timeslot-grid">
                                {slot.times.map((time: string, j: number) => (
                                    <button
                                        key={j}
                                        class="timeslot-btn"
                                        onClick={() =>
                                            fetchStep('confirm_slot', `${slot.label} à ${time}`)
                                        }
                                    >
                                        {time}
                                    </button>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {currentStep?.type === 'radio' && (
                <form onSubmit={handleSubmit} class="chatbot__form chatbot__radios">
                    {currentStep.options?.map((op, i) => (
                        <label key={i} class="chatbot__checkbox-label">
                            <input
                                type="radio"
                                value={op}
                                checked={selectedRadio === op}
                                onChange={e => setSelectedRadio((e.target as HTMLInputElement).value)}
                            />
                            {op}
                        </label>
                    ))}
                    <button type="submit" class="chatbot__button">
                        Valider
                    </button>
                </form>
            )}
        </div>
    );
}
