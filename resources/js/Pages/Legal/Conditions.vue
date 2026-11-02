<script setup>
import LegalLayout from '@/Components/LegalLayout.vue'

defineProps({ version: String, contactEmail: String })
</script>

<template>
    <LegalLayout titre="Conditions d'utilisation" :version="version">
        <p>Bienvenue sur Oikonema, une plateforme en ligne d'administration pour les églises et les ministères. En créant un compte ou en utilisant la plateforme, vous acceptez les présentes conditions. Si vous ne les acceptez pas, merci de ne pas utiliser le service.</p>

        <h2>1. Ce que fait Oikonema</h2>
        <p>Oikonema aide un ministère à tenir ses registres : membres, parcours de disciple, activités et cultes, finances, inventaire, annonces, documents, signalements. La plateforme est un outil d'organisation. Elle ne remplace ni un comptable, ni un conseil juridique, ni les obligations légales propres à votre ministère.</p>

        <h2>2. Votre compte</h2>
        <ul>
            <li>Vous devez fournir des informations exactes et garder votre mot de passe secret.</li>
            <li>Vous êtes responsable de ce qui est fait depuis votre compte. Prévenez-nous sans délai si vous pensez qu'il a été utilisé par quelqu'un d'autre.</li>
            <li>Le responsable du ministère décide qui reçoit un accès et avec quels droits. Il lui revient de retirer l'accès d'une personne qui quitte la responsabilité concernée.</li>
        </ul>

        <h2>3. Vos données vous appartiennent</h2>
        <p>Les informations que vous saisissez (membres, finances, documents, etc.) restent la propriété de votre ministère. Nous ne les vendons pas et ne les utilisons pas pour de la publicité. Chaque ministère est isolé des autres : un ministère ne peut pas voir les données d'un autre. Voir aussi la <a href="/confidentialite" class="text-sanctuary underline">politique de confidentialité</a>.</p>

        <h2>4. Responsabilité de votre ministère sur les données de ses membres</h2>
        <p>Votre ministère décide quelles informations sur ses membres il enregistre et pourquoi. Il lui revient d'informer ses membres, d'obtenir leur accord quand c'est nécessaire, et de ne saisir que ce qui est utile. Les informations liées à la religion sont sensibles : saisissez-les avec soin et réservez leur accès aux personnes qui en ont besoin. Si des mineurs sont concernés, l'accord de leurs parents ou tuteurs relève de votre ministère.</p>

        <h2>5. Usages interdits</h2>
        <ul>
            <li>Tenter d'accéder aux données d'un autre ministère ou de contourner la sécurité.</li>
            <li>Saisir des contenus illégaux, haineux, diffamatoires ou portant atteinte à la vie privée d'autrui.</li>
            <li>Utiliser la plateforme pour du harcèlement, de la fraude ou du blanchiment d'argent.</li>
            <li>Perturber le service (attaques, envois massifs automatisés).</li>
        </ul>
        <p>En cas de manquement grave, nous pouvons suspendre l'accès concerné, en prévenant la personne ou le ministère si c'est possible.</p>

        <h2>6. Essai gratuit, abonnement et paiement</h2>
        <ul>
            <li>Un nouveau ministère bénéficie d'une période d'essai gratuite de 30 jours.</li>
            <li>Ensuite, l'usage continu suppose un abonnement dont les offres et les prix sont affichés dans la rubrique « Abonnement » de la plateforme, avant tout paiement.</li>
            <li>Le paiement se fait par mobile money ou carte via notre prestataire FedaPay, ou par cryptomonnaie (USDT sur BNB Smart Chain) vers l'adresse publique affichée à l'écran. Nous ne voyons ni ne conservons vos codes mobile money ni vos numéros de carte, qui sont traités par le prestataire.</li>
            <li>Un paiement en cryptomonnaie est irréversible : vérifiez le réseau, l'adresse et le montant avant d'envoyer. Un envoi vers un mauvais réseau ou une mauvaise adresse ne peut pas être récupéré par nous.</li>
            <li>Sauf obligation légale contraire, les sommes payées pour une période déjà commencée ne sont pas remboursées. Si vous pensez qu'une erreur de paiement a eu lieu, écrivez-nous avec la preuve de paiement et nous l'examinerons avec bienveillance.</li>
        </ul>

        <h2>7. Fin de l'abonnement et récupération de vos données</h2>
        <p>Si l'abonnement n'est pas renouvelé, l'accès aux fonctions d'écriture peut être limité, mais votre ministère garde la possibilité de consulter et de récupérer ses données pendant une période raisonnable. Vous pouvez demander l'export ou la suppression de vos données à tout moment (voir la politique de confidentialité).</p>

        <h2>8. Disponibilité du service</h2>
        <p>Nous faisons le maximum pour que la plateforme soit disponible et sauvegardée chaque jour, mais nous ne pouvons pas garantir un service sans interruption ni sans erreur. Le mode hors connexion enregistre certaines saisies sur votre appareil puis les envoie dès le retour du réseau : conservez votre appareil et ne vous déconnectez pas avant l'envoi. La plateforme peut évoluer ; les changements importants vous seront signalés.</p>

        <h2>9. Assistant « Frère David »</h2>
        <p>L'assistant répond à vos questions d'utilisation avec une intelligence artificielle. Ses réponses peuvent contenir des erreurs : vérifiez les informations importantes, notamment financières ou juridiques. Ne lui transmettez pas de secrets (mots de passe, confessions, données médicales).</p>

        <h2>10. Responsabilité</h2>
        <p>Dans les limites permises par la loi, Oikonema n'est pas responsable des pertes indirectes (manque à gagner, perte d'opportunité), ni des dommages venant d'un usage contraire à ces conditions, d'un mot de passe partagé ou d'un événement hors de notre contrôle (panne de réseau, d'électricité, d'un prestataire). Rien dans ces conditions n'exclut une responsabilité qui ne peut pas l'être légalement.</p>

        <h2>11. Modifications</h2>
        <p>Nous pouvons mettre à jour ces conditions. La date de version figure en haut de la page. En cas de changement important, nous vous demanderons de les accepter à nouveau.</p>

        <h2>12. Droit applicable</h2>
        <p>Ces conditions sont régies par le droit de la République du Bénin. Avant toute action en justice, nous vous invitons à chercher d'abord une solution amiable en nous écrivant.</p>

        <h2>13. Nous contacter</h2>
        <p v-if="contactEmail">Pour toute question : <a :href="'mailto:' + contactEmail" class="text-sanctuary underline">{{ contactEmail }}</a>.</p>
        <p v-else>Pour toute question, utilisez la rubrique « Aide » de la plateforme ou contactez l'équipe Oikonema qui vous a ouvert l'accès.</p>
    </LegalLayout>
</template>
