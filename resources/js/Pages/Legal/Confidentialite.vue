<script setup>
import LegalLayout from '@/Components/LegalLayout.vue'

defineProps({ version: String, contactEmail: String })
</script>

<template>
    <LegalLayout titre="Politique de confidentialité" :version="version">
        <p>Cette page explique, simplement, quelles informations Oikonema conserve, pourquoi, où, et quels sont vos droits. Elle s'appuie sur la loi béninoise relative à la protection des données personnelles (Code du numérique).</p>

        <h2>1. Qui est responsable de quoi</h2>
        <ul>
            <li><strong>Votre ministère</strong> décide quelles informations sur ses membres il enregistre. Il en est le responsable.</li>
            <li><strong>Oikonema</strong> fournit l'outil et héberge les données pour le compte du ministère. Nous agissons selon ses instructions et pour faire fonctionner le service.</li>
        </ul>

        <h2>2. Les informations que nous conservons</h2>
        <ul>
            <li><strong>Votre compte</strong> : nom, adresse e-mail, téléphone (facultatif), mot de passe (conservé sous forme chiffrée, jamais lisible).</li>
            <li><strong>Les registres du ministère</strong> : fiches de membres (y compris photo et étapes du parcours de disciple), activités et cultes, finances, inventaire, annonces, documents, signalements. Les informations qui révèlent des convictions religieuses sont sensibles et sont traitées comme telles.</li>
            <li><strong>Le ministère lui-même</strong> : nom, adresse, contacts, numéro d'autorisation, logo.</li>
            <li><strong>Abonnement</strong> : l'offre choisie, les dates, l'état et la référence des paiements. Nous ne conservons pas vos numéros de carte ni vos codes mobile money.</li>
            <li><strong>Acceptation des conditions</strong> : la date et la version que vous avez acceptées.</li>
            <li><strong>Données techniques</strong> : journaux de sécurité et d'erreurs (adresse IP, date, page demandée) pour protéger le service et corriger les pannes.</li>
        </ul>

        <h2>3. Pourquoi, et sur quelle base</h2>
        <p>Pour créer votre compte, faire fonctionner les modules, gérer l'abonnement, assurer la sécurité et les sauvegardes, et vous aider via l'assistant. Nous n'utilisons pas vos données pour de la publicité et nous ne les vendons pas.</p>

        <h2>4. Où sont les données et qui peut les voir</h2>
        <ul>
            <li>Les données sont hébergées sur un serveur de notre prestataire Hetzner (centre de données en Finlande, Union européenne).</li>
            <li>Chaque ministère est isolé par une barrière technique dans la base de données : un ministère ne peut pas lire les données d'un autre.</li>
            <li>Au sein d'un ministère, chacun voit seulement ce que ses droits permettent. L'équipe technique d'Oikonema peut accéder aux données uniquement pour dépanner ou sécuriser le service.</li>
            <li>Une sauvegarde est réalisée chaque nuit, dans un espace du serveur à accès restreint.</li>
        </ul>

        <h2>5. Les prestataires qui interviennent</h2>
        <ul>
            <li><strong>FedaPay</strong> : traite les paiements par mobile money et carte. Il reçoit les informations nécessaires au paiement (montant, nom, contact).</li>
            <li><strong>Blockchain BNB Smart Chain</strong> : pour les paiements en USDT, la transaction est publique par nature sur la blockchain (adresses et montants), sans nom.</li>
            <li><strong>Services de taux de change publics</strong> : consultés pour convertir les montants, sans transmettre de données personnelles.</li>
            <li><strong>Assistant « Frère David »</strong> : quand vous lui écrivez, le texte de votre question (et un fichier joint, si vous en ajoutez un) est envoyé à un fournisseur d'intelligence artificielle (Anthropic) pour produire la réponse. N'y écrivez pas d'informations confidentielles sur des personnes.</li>
            <li><strong>Courriel</strong> : le service d'envoi de courriels reçoit l'adresse du destinataire et le message (invitation, par exemple).</li>
        </ul>
        <p>Nous ne communiquons pas vos données à d'autres tiers, sauf obligation légale.</p>

        <h2>6. Sur votre téléphone ou ordinateur</h2>
        <p>Pour fonctionner hors connexion, l'application garde sur votre appareil une copie des pages consultées et, temporairement, les saisies en attente d'envoi. Ce stockage local est effacé à la déconnexion. Nous n'utilisons pas de cookies publicitaires ni de traceurs d'audience : seul un cookie de session nécessaire à la connexion est utilisé. Sur un appareil partagé, déconnectez-vous toujours.</p>

        <h2>7. Combien de temps</h2>
        <p>Les données sont conservées tant que le compte et le ministère sont actifs. À la suppression d'un compte ou d'un ministère, les données sont effacées de la base active ; elles peuvent subsister dans les sauvegardes pendant au plus quelques mois (14 jours pour les sauvegardes courantes, jusqu'à 6 mois pour la copie mensuelle). Certaines pièces (preuves de paiement) peuvent être gardées plus longtemps si la loi l'impose.</p>

        <h2>8. Vos droits</h2>
        <p>Vous pouvez demander à accéder à vos données, à les faire corriger, à les faire supprimer, à vous opposer à un usage, ou à en recevoir une copie. Pour les informations d'un membre, adressez-vous d'abord au responsable de votre ministère ; il dispose des outils pour agir. Vous pouvez aussi nous écrire (voir plus bas). Si vous estimez que vos droits ne sont pas respectés, vous pouvez saisir l'autorité béninoise de protection des données personnelles (APDP).</p>

        <h2>9. Mineurs</h2>
        <p>Les comptes sont destinés aux adultes. Si un ministère enregistre des enfants parmi ses membres, c'est sous sa responsabilité et avec l'accord de leurs parents ou tuteurs.</p>

        <h2>10. Sécurité</h2>
        <p>Connexion chiffrée (HTTPS), mots de passe chiffrés, isolation entre ministères, droits d'accès par rôle, sauvegardes quotidiennes. Aucun système n'est parfait : en cas d'incident touchant vos données, nous préviendrons les ministères concernés et les autorités dans les délais légaux.</p>

        <h2>11. Changements</h2>
        <p>La date de version figure en haut de cette page. Les changements importants vous seront signalés.</p>

        <h2>12. Nous contacter</h2>
        <p v-if="contactEmail">Pour exercer vos droits ou poser une question : <a :href="'mailto:' + contactEmail" class="text-sanctuary underline">{{ contactEmail }}</a>.</p>
        <p v-else>Pour exercer vos droits ou poser une question, utilisez la rubrique « Aide » de la plateforme ou contactez l'équipe Oikonema qui vous a ouvert l'accès.</p>
    </LegalLayout>
</template>
