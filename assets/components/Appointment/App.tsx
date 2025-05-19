import { useEffect, useState } from 'preact/hooks';

export function App() {
    // Ancien state (plus utilisé)
    // const [data, setData] = useState<any>(null);

    useEffect(() => {
        // Injection du script Dialogflow Messenger
        const script = document.createElement('script');
        script.src = 'https://www.gstatic.com/dialogflow-console/fast/messenger/bootstrap.js?v=1';
        script.async = true;
        document.body.appendChild(script);

        // Nettoyage au démontage du composant
        return () => {
            document.body.removeChild(script);
        };
    }, []);

    // Ancienne fonction d'appel à l'API publique (désactivée)
    /*
    async function fetchData() {
        try {
            const response = await fetch(
                'https://data.education.gouv.fr/api/explore/v2.1/catalog/datasets/fr-en-annuaire-education/records?limit=20',
                {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                        // ⚠️ PAS de 'Content-Type' ici pour éviter le preflight bloqué
                    }
                }
            );

            console.log("Response status:", response.status);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log(data);
            setData(data);
        } catch (error) {
            console.error('There was a problem with the fetch operation:', error);
        }
    }
    */

    return (
        <div>
            <h2>Bienvenue sur notre assistant Garage !</h2>

            {/* Widget Dialogflow Messenger */}
            <df-messenger
                intent="WELCOME"
                chat-title="GarageAgent"
                agent-id="376a60c7-9b88-49c1-af81-e0d4d144fba6"
                language-code="fr"
            ></df-messenger>
        </div>
    );
}
