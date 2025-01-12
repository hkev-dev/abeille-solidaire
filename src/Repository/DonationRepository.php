<?php

namespace App\Repository;

use App\Entity\Donation;
use App\Entity\User;
use App\Entity\Flower;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DonationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Donation::class);
    }

    public function findUserDonationsInFlower(User $user, Flower $flower): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.recipient = :user')
            ->andWhere('d.flower = :flower')
            ->andWhere('d.donationType IN (:types)')
            ->setParameter('user', $user)
            ->setParameter('flower', $flower)
            ->setParameter('types', [Donation::TYPE_DIRECT, Donation::TYPE_SOLIDARITY])
            ->orderBy('d.transactionDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countDonationsInCycle(User $user, Flower $flower): int
    {
        return $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.recipient = :user')
            ->andWhere('d.flower = :flower')
            ->andWhere('d.donationType IN (:types)')
            ->setParameter('user', $user)
            ->setParameter('flower', $flower)
            ->setParameter('types', [Donation::TYPE_DIRECT, Donation::TYPE_SOLIDARITY])
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findPendingDonations(): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.stripePaymentIntentId IS NOT NULL')
            ->andWhere('d.transactionDate >= :date')
            ->setParameter('date', new \DateTime('-24 hours'))
            ->getQuery()
            ->getResult();
    }

    public function getReferrerMatrixPositions(User $referrer, Flower $flower): array
    {
        $qb = $this->createQueryBuilder('d')
            ->select('d.cyclePosition', 'IDENTITY(d.recipient) as recipient_id')
            ->where('d.flower = :flower')
            ->andWhere('d.donor = :referrer')
            ->setParameter('flower', $flower)
            ->setParameter('referrer', $referrer)
            ->andWhere('d.donationType IN (:types)')
            ->setParameter('types', ['direct', 'registration', 'referral_placement']);

        $result = $qb->getQuery()->getResult();

        $positions = [];
        foreach ($result as $row) {
            $positions[$row['cyclePosition']] = $row['recipient_id'];
        }

        return $positions;
    }

    public function findByFlowerMatrix(Flower $flower): array
    {
        return $this->createQueryBuilder('d')
            ->select('d.cyclePosition', 'IDENTITY(d.recipient) as recipient_id')
            ->where('d.flower = :flower')
            ->andWhere('d.donationType IN (:types)')
            ->setParameter('flower', $flower)
            ->setParameter('types', ['direct', 'registration'])
            ->orderBy('d.cyclePosition', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByReferrerMatrix(User $referrer, Flower $flower): array
    {
        $qb = $this->createQueryBuilder('d')
            ->select('d.cyclePosition', 'IDENTITY(d.recipient) as recipient_id')
            ->where('d.flower = :flower')
            ->andWhere('d.donor = :referrer')
            ->andWhere('d.donationType IN (:types)')
            ->setParameter('flower', $flower)
            ->setParameter('referrer', $referrer)
            ->setParameter('types', ['direct', 'registration', 'referral_placement']);

        $result = $qb->getQuery()->getResult();

        $positions = [];
        foreach ($result as $row) {
            $positions[$row['cyclePosition']] = $row['recipient_id'];
        }

        return $positions;
    }

    public function getTotalReceivedByUser(User $user): float
    {
        $result = $this->createQueryBuilder('d')
            ->select('SUM(d.amount)')
            ->where('d.recipient = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result ?? 0.0;
    }

    public function getTotalMadeByUser(User $user): float
    {
        $result = $this->createQueryBuilder('d')
            ->select('SUM(d.amount)')
            ->where('d.donor = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result ?? 0.0;
    }

    public function getCurrentFlowerProgress(User $user): array
    {
        $qb = $this->createQueryBuilder('d');
        $received = $qb->select('COUNT(d.id)')
            ->where('d.recipient = :user')
            ->andWhere('d.flower = :flower')
            ->andWhere('d.donationType = :type')
            ->setParameter('user', $user)
            ->setParameter('flower', $user->getCurrentFlower())
            ->setParameter('type', 'direct')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'received' => (int) $received,
            'total' => 4,
            'percentage' => ($received / 4) * 100
        ];
    }

    /**
     * @return Donation[]
     */
    public function findRecentByUser(User $user, int $limit): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.recipient = :user')
            ->setParameter('user', $user)
            ->orderBy('d.transactionDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByFlowerAndRecipient(Flower $flower, User $recipient, int $limit): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.flower = :flower')
            ->andWhere('d.recipient = :recipient')
            ->andWhere('d.donationType IN (:types)')
            ->setParameter('flower', $flower)
            ->setParameter('recipient', $recipient)
            ->setParameter('types', ['direct', 'registration', 'referral_placement'])
            ->orderBy('d.transactionDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getTotalReceivedInFlower(User $user, Flower $flower): float
    {
        $result = $this->createQueryBuilder('d')
            ->select('SUM(d.amount)')
            ->where('d.flower = :flower')
            ->andWhere('d.recipient = :recipient')
            ->andWhere('d.donationType IN (:types)')
            ->setParameter('flower', $flower)
            ->setParameter('recipient', $user)
            ->setParameter('types', ['direct', 'registration', 'referral_placement'])
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result ?? 0.0;
    }

    public function getUserPositionInFlower(User $user, Flower $flower): ?int
    {
        $result = $this->createQueryBuilder('d')
            ->select('d.cyclePosition')
            ->where('d.recipient = :user')
            ->andWhere('d.flower = :flower')
            ->andWhere('d.donationType IN (:types)')
            ->setParameter('user', $user)
            ->setParameter('flower', $flower)
            ->setParameter('types', ['direct', 'registration', 'referral_placement'])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result ? $result['cyclePosition'] : null;
    }

    public function findByFlowerWithActivity(Flower $flower, int $limit): array
    {
        $donations = $this->createQueryBuilder('d')
            ->select('d', 'donor', 'recipient')
            ->join('d.donor', 'donor')
            ->join('d.recipient', 'recipient')
            ->where('d.flower = :flower')
            ->andWhere('d.donationType IN (:types)')
            ->setParameter('flower', $flower)
            ->setParameter('types', ['direct', 'registration', 'referral_placement'])
            ->orderBy('d.transactionDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        // Transform donations into activity items
        return array_map(function($donation) {
            $type = match ($donation->getDonationType()) {
                'direct' => 'primary',
                'registration' => 'success',
                'referral_placement' => 'info',
                default => 'secondary'
            };
            
            $icon = match ($donation->getDonationType()) {
                'direct' => 'gift',
                'registration' => 'user-tick',
                'referral_placement' => 'profile-circle',
                default => 'notification'
            };

            $description = match ($donation->getDonationType()) {
                'direct' => sprintf(
                    '%s a envoyé un don à %s',
                    $donation->getDonor()->getFullName(),
                    $donation->getRecipient()->getFullName()
                ),
                'registration' => sprintf(
                    '%s a rejoint la fleur',
                    $donation->getRecipient()->getFullName()
                ),
                'referral_placement' => sprintf(
                    '%s a été placé par son parrain',
                    $donation->getRecipient()->getFullName()
                ),
                default => 'Activité'
            };

            return [
                'type' => $type,
                'icon' => $icon,
                'description' => $description,
                'date' => $donation->getTransactionDate(),
                'amount' => $donation->getAmount()
            ];
        }, $donations);
    }

    public function findTotalMadeInFlower(User $user, ?Flower $flower): float
    {
        if (!$flower) {
            return 0.0;
        }

        $result = $this->createQueryBuilder('d')
            ->select('SUM(d.amount)')
            ->where('d.donor = :user')
            ->andWhere('d.flower = :flower')
            ->andWhere('d.donationType IN (:types)')
            ->setParameter('user', $user)
            ->setParameter('flower', $flower)
            ->setParameter('types', ['direct', 'registration', 'referral_placement'])
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result ?? 0.0;
    }

    public function findTotalSolidarityReceived(User $user): float
    {
        $result = $this->createQueryBuilder('d')
            ->select('SUM(d.amount)')
            ->where('d.recipient = :user')
            ->andWhere('d.donationType = :type')
            ->setParameter('user', $user)
            ->setParameter('type', 'solidarity')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result ?? 0.0;
    }

    public function findTotalSolidarityDistributed(User $user): float
    {
        $result = $this->createQueryBuilder('d')
            ->select('SUM(d.amount)')
            ->where('d.donor = :user')
            ->andWhere('d.donationType = :type')
            ->setParameter('user', $user)
            ->setParameter('type', 'solidarity')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result ?? 0.0;
    }

    public function findTotalReferralEarningsForUser(User $user): float
    {
        $result = $this->createQueryBuilder('d')
            ->select('SUM(d.amount)')
            ->where('d.recipient = :user')
            ->andWhere('d.donationType IN (:types)')
            ->setParameter('user', $user)
            ->setParameter('types', ['referral_placement', 'solidarity'])
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result ?? 0.0;
    }

    public function findMonthlyReferralEarnings(User $user): array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = '
            SELECT to_char(transaction_date, \'YYYY-MM\') as month,
                   SUM(amount) as total
            FROM donation
            WHERE recipient_id = :userId
            AND donation_type IN (:types)
            AND transaction_date >= :yearAgo
            GROUP BY month
            ORDER BY month ASC
        ';

        $stmt = $conn->executeQuery(
            $sql,
            [
                'userId' => $user->getId(),
                'types' => ['referral_placement', 'solidarity'],
                'yearAgo' => (new \DateTime('-1 year'))->format('Y-m-d')
            ],
            [
                'types' => \Doctrine\DBAL\Connection::PARAM_STR_ARRAY
            ]
        );

        $results = $stmt->fetchAllAssociative();
        
        $earnings = [];
        foreach ($results as $result) {
            $earnings[$result['month']] = (float)$result['total'];
        }

        return $earnings;
    }

    public function findUserPositionInFlower(User $user, Flower $flower): ?array
    {
        $result = $this->createQueryBuilder('d')
            ->select('d.cyclePosition as cycle_position') // Fixed: Added alias for cyclePosition
            ->where('d.recipient = :user')
            ->andWhere('d.flower = :flower')
            ->andWhere('d.donationType IN (:types)')
            ->setParameter('user', $user)
            ->setParameter('flower', $flower)
            ->setParameter('types', ['direct', 'registration', 'referral_placement'])
            ->getQuery()
            ->getOneOrNullResult();

        return $result ?: null;
    }

    public function calculateTotalReceivedInFlower(User $user, Flower $flower): float
    {
        $result = $this->createQueryBuilder('d')
            ->select('SUM(d.amount) as total')
            ->where('d.recipient = :user')
            ->andWhere('d.flower = :flower')
            ->andWhere('d.donationType IN (:types)')
            ->setParameter('user', $user)
            ->setParameter('flower', $flower)
            ->setParameter('types', ['direct', 'registration'])
            ->getQuery()
            ->getSingleScalarResult();

        return (float)$result ?? 0.0;
    }

    public function findAllCompletedCycles(User $user): array
    {
        $qb = $this->createQueryBuilder('d')
            ->select([
                'IDENTITY(d.flower) as flower_id',
                'COUNT(d.id) as cycle_number',
                'MAX(d.transactionDate) as completed_at',
                'SUM(CASE WHEN d.donationType IN (\'direct\', \'registration\') THEN d.amount ELSE 0 END) as earned_amount',
                'SUM(CASE WHEN d.donationType = \'solidarity\' THEN d.amount ELSE 0 END) as solidarity_amount'
            ])
            ->where('d.recipient = :user')
            ->andWhere('d.donationType IN (:types)')
            ->groupBy('d.flower')
            ->having('COUNT(d.id) >= 4')
            ->setParameter('user', $user)
            ->setParameter('types', ['direct', 'registration', 'solidarity'])
            ->orderBy('d.flower', 'ASC');

        $results = $qb->getQuery()->getResult();

        // Fetch flower details and solidarity recipients
        return array_map(function($cycle) use ($user) {
            $flower = $this->getEntityManager()
                ->getRepository('App:Flower')
                ->find($cycle['flower_id']);

            $solidarityRecipient = $this->createQueryBuilder('d2')
                ->select('IDENTITY(d2.recipient) as recipient_id')
                ->where('d2.donor = :user')
                ->andWhere('d2.flower = :flower')
                ->andWhere('d2.donationType = :type')
                ->setParameter('user', $user)
                ->setParameter('flower', $flower)
                ->setParameter('type', 'solidarity')
                ->getQuery()
                ->getOneOrNullResult();

            $recipientUser = $solidarityRecipient ? 
                $this->getEntityManager()->getRepository('App:User')->find($solidarityRecipient['recipient_id']) : 
                null;

            return [
                'flower' => $flower,
                'cycleNumber' => $cycle['cycle_number'],
                'completedAt' => $cycle['completed_at'],
                'earned' => $cycle['earned_amount'],
                'solidarityAmount' => $cycle['solidarity_amount'],
                'solidarityRecipient' => $recipientUser
            ];
        }, $results);
    }

    public function calculateTotalEarned(User $user): float
    {
        $result = $this->createQueryBuilder('d')
            ->select('SUM(d.amount) as total')
            ->where('d.recipient = :user')
            ->andWhere('d.donationType IN (:types)')
            ->setParameter('user', $user)
            ->setParameter('types', ['direct', 'registration'])
            ->getQuery()
            ->getSingleScalarResult();

        return (float)$result ?? 0.0;
    }

    public function findReferralsInFlower(User $user, Flower $flower): array
    {
        return $this->createQueryBuilder('d')
            ->select('
                IDENTITY(d.recipient) as user,
                d.cyclePosition as position,
                d.transactionDate as joined_at
            ')
            ->where('d.flower = :flower')
            ->andWhere('d.donationType = :type')
            ->andWhere('d.recipient IN (
                SELECT r.id FROM App\Entity\User r WHERE r.referrer = :user
            )')
            ->setParameter('flower', $flower)
            ->setParameter('type', 'referral_placement')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function findByFlowerWithMatrix(Flower $flower): array
    {
        $results = $this->createQueryBuilder('d')
            ->select('
                d.cyclePosition,
                IDENTITY(d.recipient) as user_id,
                d.transactionDate as joined_at
            ')
            ->where('d.flower = :flower')
            ->andWhere('d.donationType IN (:types)')
            ->setParameter('flower', $flower)
            ->setParameter('types', ['direct', 'registration', 'referral_placement'])
            ->orderBy('d.cyclePosition', 'ASC')
            ->getQuery()
            ->getResult();

        $positions = [];
        foreach ($results as $result) {
            $positions[$result['cyclePosition']] = [
                'user_id' => $result['user_id'],
                'joined_at' => $result['joined_at']
            ];
        }

        return $positions;
    }
}
